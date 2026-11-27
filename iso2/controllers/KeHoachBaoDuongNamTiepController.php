<?php
declare(strict_types=1);

require_once __DIR__ . '/KeHoachBaoDuongDinhKyController.php';

/**
 * Lập kế hoạch bảo dưỡng định kỳ cho năm kế tiếp (2027),
 * lấy danh sách thiết bị và kết quả thực hiện năm 2026 làm tham khảo.
 */
class KeHoachBaoDuongNamTiepController extends KeHoachBaoDuongDinhKyController
{
    public const NAM_KE_HOACH = 2027;
    public const NAM_THAM_KHAO = 2026;
    private const PLAN_MARK = 'TO';
    private const GHI_CHU_MAX = 255;

    public function index(): void
    {
        if (!isset($_SESSION['kehoach_hoso_csrf'])) {
            $_SESSION['kehoach_hoso_csrf'] = bin2hex(random_bytes(32));
        }
        $namKeHoach = self::NAM_KE_HOACH;
        $namThamKhao = self::NAM_THAM_KHAO;
        $search = trim((string)($_GET['search'] ?? ''));
        $trangthai = (string)($_GET['trangthai'] ?? '');
        $csrfToken = $_SESSION['kehoach_hoso_csrf'];

        try {
            $rows = $this->buildRows($search);
        } catch (Exception $e) {
            error_log('Error in KeHoachBaoDuongNamTiepController::index: ' . $e->getMessage());
            http_response_code(500);
            exit('Không tải được dữ liệu kế hoạch. Vui lòng kiểm tra PHP error log.');
        }

        foreach ($rows as &$row) {
            $row['pending'] = $row['plan'] === null && $row['ref'] !== null
                && implode('', $this->defaultQuarters($row['ref'])) !== '';
            $row['quarters'] = [];
            for ($q = 1; $q <= 4; $q++) {
                $row['quarters'][$q] = $row['plan'] !== null
                    ? $this->isQuarterSelected($row['plan'], $q)
                    : $this->defaultQuarters($row['ref'])[$q] !== '';
            }
        }
        unset($row);
        $pendingCount = count(array_filter($rows, static fn(array $row): bool => $row['pending']));

        if ($trangthai === 'chualap' || $trangthai === 'dalap') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($trangthai): bool {
                $hasPlan = in_array(true, $row['quarters'], true);
                return $trangthai === 'dalap' ? $hasPlan : !$hasPlan;
            }));
        }

        $quarterTotals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        foreach ($rows as $row) {
            foreach ($quarterTotals as $q => $_) {
                if ($row['quarters'][$q]) {
                    $quarterTotals[$q]++;
                }
            }
        }
        $total = count($rows);
        $donViOptions = ['KTKT', 'ĐVLTH', 'CNM', 'RDNGA', 'TV'];

        require __DIR__ . '/../views/kehoachbaoduongdinhky/ke_hoach_nam_tiep.php';
    }

    /**
     * Lưu chọn / bỏ chọn một quý cho kế hoạch năm kế tiếp.
     */
    public function saveQuarter(): void
    {
        $input = $this->readJsonInput();
        $quarters = $this->parseQuarters($input['quarters'] ?? null);
        if ($quarters === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Quý không hợp lệ'], 422);
        }

        try {
            $plan = $this->resolvePlan((int)($input['plan_id'] ?? 0), (int)($input['ref_id'] ?? 0), $quarters);
            if ($plan === null) {
                $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thiết bị hoặc bạn không có quyền'], 404);
            }

            $stmt = $this->db->prepare(
                "UPDATE ke_hoach_bao_duong_dinh_ky_iso
                 SET qui_1 = :q1, qui_2 = :q2, qui_3 = :q3, qui_4 = :q4
                 WHERE id = :id AND nam = :nam"
            );
            $stmt->execute([
                ':q1' => $quarters[1], ':q2' => $quarters[2], ':q3' => $quarters[3], ':q4' => $quarters[4],
                ':id' => $plan['id'], ':nam' => self::NAM_KE_HOACH,
            ]);
            logBaoDuongActivity($this->db, 'UPDATE', (int)$plan['id'], null, [
                'operation' => 'plan_next_year_quarter', 'nam' => self::NAM_KE_HOACH,
                'qui_1' => $quarters[1], 'qui_2' => $quarters[2], 'qui_3' => $quarters[3], 'qui_4' => $quarters[4],
            ]);

            $this->jsonResponse(['success' => true, 'plan_id' => (int)$plan['id']]);
        } catch (Exception $e) {
            error_log('Error in saveQuarter: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu kế hoạch'], 500);
        }
    }

    /**
     * Lưu hàng loạt các quý đã hoàn thành năm 2026 làm kế hoạch 2027
     * cho những thiết bị chưa có dòng kế hoạch 2027.
     */
    public function applyDefaults(): void
    {
        $input = $this->readJsonInput();
        try {
            $created = 0;
            foreach ($this->buildRows(trim((string)($input['search'] ?? ''))) as $row) {
                if ($row['plan'] !== null || $row['ref'] === null) {
                    continue;
                }
                $quarters = $this->defaultQuarters($row['ref']);
                if (implode('', $quarters) === '') {
                    continue;
                }
                if ($this->resolvePlan(0, (int)$row['ref']['id'], $quarters) !== null) {
                    $created++;
                }
            }
            $this->jsonResponse(['success' => true, 'created' => $created]);
        } catch (Exception $e) {
            error_log('Error in applyDefaults: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu kế hoạch'], 500);
        }
    }

    /**
     * Lưu đơn vị làm chính / phụ cho kế hoạch năm kế tiếp.
     */
    public function saveDonVi(): void
    {
        $input = $this->readJsonInput();
        $chinh = trim((string)($input['donvi_lam_chinh'] ?? ''));
        $phu = trim((string)($input['donvi_lam_phu'] ?? ''));
        if (mb_strlen($chinh) > 50 || mb_strlen($phu) > 50) {
            $this->jsonResponse(['success' => false, 'message' => 'Tên đơn vị quá dài'], 422);
        }

        try {
            $plan = $this->resolvePlan(
                (int)($input['plan_id'] ?? 0),
                (int)($input['ref_id'] ?? 0),
                $this->parseQuarters($input['quarters'] ?? null)
            );
            if ($plan === null) {
                $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thiết bị hoặc bạn không có quyền'], 404);
            }

            $stmt = $this->db->prepare(
                "UPDATE ke_hoach_bao_duong_dinh_ky_iso
                 SET donvi_lam_chinh = :chinh, donvi_lam_phu = :phu
                 WHERE id = :id AND nam = :nam"
            );
            $stmt->execute([':chinh' => $chinh, ':phu' => $phu, ':id' => $plan['id'], ':nam' => self::NAM_KE_HOACH]);
            logBaoDuongActivity($this->db, 'UPDATE', (int)$plan['id'], null, [
                'operation' => 'plan_next_year_units', 'nam' => self::NAM_KE_HOACH,
                'donvi_lam_chinh' => $chinh, 'donvi_lam_phu' => $phu,
            ]);

            $this->jsonResponse(['success' => true, 'plan_id' => (int)$plan['id']]);
        } catch (Exception $e) {
            error_log('Error in saveDonVi: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu đơn vị'], 500);
        }
    }

    /**
     * Lưu ghi chú cho kế hoạch năm kế tiếp.
     */
    public function saveGhiChu(): void
    {
        $input = $this->readJsonInput();
        $ghiChu = trim((string)($input['ghi_chu'] ?? ''));
        if (mb_strlen($ghiChu) > self::GHI_CHU_MAX) {
            $this->jsonResponse(['success' => false, 'message' => 'Ghi chú tối đa ' . self::GHI_CHU_MAX . ' ký tự'], 422);
        }

        try {
            $plan = $this->resolvePlan(
                (int)($input['plan_id'] ?? 0),
                (int)($input['ref_id'] ?? 0),
                $this->parseQuarters($input['quarters'] ?? null)
            );
            if ($plan === null) {
                $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy thiết bị hoặc bạn không có quyền'], 404);
            }

            $stmt = $this->db->prepare('UPDATE ke_hoach_bao_duong_dinh_ky_iso SET ghi_chu = :ghi_chu WHERE id = :id AND nam = :nam');
            $stmt->execute([':ghi_chu' => $ghiChu, ':id' => $plan['id'], ':nam' => self::NAM_KE_HOACH]);
            logBaoDuongActivity($this->db, 'UPDATE', (int)$plan['id'], null, [
                'operation' => 'plan_next_year_note', 'nam' => self::NAM_KE_HOACH, 'ghi_chu' => $ghiChu,
            ]);

            $this->jsonResponse(['success' => true, 'plan_id' => (int)$plan['id']]);
        } catch (Exception $e) {
            error_log('Error in saveGhiChu: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra khi lưu ghi chú'], 500);
        }
    }

    /**
     * Ghép từng dòng kế hoạch 2026 với dòng kế hoạch 2027 tương ứng (nếu đã có).
     * Dòng 2027 không có dòng 2026 tương ứng được đặt cuối danh sách.
     *
     * @return array<int, array{ref: ?array, plan: ?array}>
     */
    private function buildRows(string $search): array
    {
        $refPlans = $this->loadHoSoExecutionInfo($this->fetchPlans(self::NAM_THAM_KHAO, $search));
        $targetPlans = $this->fetchPlans(self::NAM_KE_HOACH, $search);

        $targetById = [];
        $idsByKey = [];
        foreach ($targetPlans as $target) {
            $targetById[(int)$target['id']] = $target;
            foreach ($this->planKeys($target) as $key) {
                $idsByKey[$key][] = (int)$target['id'];
            }
        }

        $used = [];
        $rows = [];
        foreach ($refPlans as $ref) {
            $matchedId = null;
            foreach ($this->planKeys($ref) as $key) {
                foreach ($idsByKey[$key] ?? [] as $candidateId) {
                    if (!isset($used[$candidateId])) {
                        $matchedId = $candidateId;
                        break 2;
                    }
                }
            }
            if ($matchedId !== null) {
                $used[$matchedId] = true;
            }
            $rows[] = ['ref' => $ref, 'plan' => $matchedId !== null ? $targetById[$matchedId] : null];
        }
        foreach ($targetById as $id => $target) {
            if (!isset($used[$id])) {
                $rows[] = ['ref' => null, 'plan' => $target];
            }
        }
        return $rows;
    }

    private function fetchPlans(int $nam, string $search): array
    {
        $sql = 'SELECT k.*, (SELECT t.ngayktsd FROM thietbi_iso t WHERE t.stt = k.thietbi_id LIMIT 1) AS ngayktsd
                FROM ke_hoach_bao_duong_dinh_ky_iso k WHERE k.nam = :nam';
        $params = [':nam' => $nam];
        $this->applyDonViFilter($sql, $params, 'k');
        if ($search !== '') {
            $sql .= ' AND (k.ten_thietbi LIKE :search1 OR k.so_serial LIKE :search2)';
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY k.ten_thietbi ASC, k.so_serial ASC, k.id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Khóa ghép dòng giữa hai năm: ID thiết bị (nếu có) rồi đến tên + số S/N.
     *
     * @return string[]
     */
    private function planKeys(array $plan): array
    {
        $keys = [];
        if ((int)($plan['thietbi_id'] ?? 0) > 0) {
            $keys[] = 'id:' . (int)$plan['thietbi_id'];
        }
        $keys[] = 'sn:' . mb_strtolower(trim((string)($plan['ten_thietbi'] ?? '')), 'UTF-8')
            . '|' . mb_strtolower(trim((string)($plan['so_serial'] ?? '')), 'UTF-8');
        return $keys;
    }

    /**
     * Thêm một thiết bị mới (nhập tay) vào kế hoạch năm kế tiếp.
     * Nếu tên + S/N chưa có trong thietbi_iso thì tự tạo thiết bị mới (thuộc đơn vị của người dùng)
     * rồi gắn vào kế hoạch; nếu đã có trong danh sách kế hoạch thì từ chối.
     */
    public function addRow(): void
    {
        $input = $this->readJsonInput();
        $ten = trim((string)($input['ten_thietbi'] ?? ''));
        $serial = trim((string)($input['so_serial'] ?? ''));
        try {
            $ngayKtsd = $this->parseManufacturingYear($input['nam_sx'] ?? null);
        } catch (InvalidArgumentException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $chinh = trim((string)($input['donvi_lam_chinh'] ?? ''));
        $phu = trim((string)($input['donvi_lam_phu'] ?? ''));
        $ghiChu = trim((string)($input['ghi_chu'] ?? ''));
        $quarters = $this->parseQuarters($input['quarters'] ?? null) ?? [1 => '', 2 => '', 3 => '', 4 => ''];

        if ($ten === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng nhập tên thiết bị'], 422);
        }
        if (mb_strlen($ten) > 255 || mb_strlen($serial) > 100 || mb_strlen($chinh) > 50 || mb_strlen($phu) > 50
            || mb_strlen($ghiChu) > self::GHI_CHU_MAX) {
            $this->jsonResponse(['success' => false, 'message' => 'Dữ liệu nhập quá dài'], 422);
        }

        try {
            $thietbiId = $this->findThietbiId($ten, $serial);
            $deviceExistsElsewhere = $thietbiId === null && !$this->isAdmin()
                && $this->findThietbiId($ten, $serial, false) !== null;
            if ($deviceExistsElsewhere) {
                $this->jsonResponse(['success' => false, 'message' => 'Thiết bị trùng tên + S/N đã thuộc đơn vị khác'], 409);
            }

            $candidateKeys = $this->planKeys(['thietbi_id' => $thietbiId, 'ten_thietbi' => $ten, 'so_serial' => $serial]);
            foreach ($this->buildRows('') as $row) {
                foreach (array_filter([$row['ref'], $row['plan']]) as $existing) {
                    if (array_intersect($candidateKeys, $this->planKeys($existing)) !== []) {
                        $this->jsonResponse(['success' => false, 'message' => 'Thiết bị này đã có trong danh sách'], 409);
                    }
                }
            }

            $madv = $this->getUserMadv();
            if ($thietbiId === null && $madv === '' && !$this->isAdmin()) {
                $this->jsonResponse(['success' => false, 'message' => 'Tài khoản chưa có đơn vị nên không thể thêm thiết bị mới'], 422);
            }
            if ($thietbiId === null && $madv === '' && $ngayKtsd !== null) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Không thể lưu Năm SX vì thiết bị chưa có trong danh mục thiết bị',
                ], 422);
            }

            $deviceCreated = false;
            $this->db->beginTransaction();
            if ($thietbiId === null && $madv !== '') {
                $thietbiId = $this->createDevice($ten, $serial, $madv, $ngayKtsd ?? '1970-01-01');
                $deviceCreated = true;
            } elseif ($thietbiId !== null && $ngayKtsd !== null) {
                $stmt = $this->db->prepare('UPDATE thietbi_iso SET ngayktsd = :ngayktsd WHERE stt = :stt');
                $stmt->execute([':ngayktsd' => $ngayKtsd, ':stt' => $thietbiId]);
            }
            $newId = $this->insertPlan([
                'thietbi_id' => $thietbiId, 'ten_thietbi' => $ten, 'so_serial' => $serial, 'nhomsc' => '',
                'donvi_lam_chinh' => $chinh, 'donvi_lam_phu' => $phu, 'ghi_chu' => $ghiChu,
            ], $quarters);
            $this->db->commit();

            logBaoDuongActivity($this->db, 'INSERT', $newId, null, [
                'operation' => 'plan_next_year_add_row', 'nam' => self::NAM_KE_HOACH,
                'ten_thietbi' => $ten, 'so_serial' => $serial, 'thietbi_id' => $thietbiId,
                'device_created' => $deviceCreated, 'ngayktsd' => $ngayKtsd,
            ]);
            $this->jsonResponse(['success' => true, 'plan_id' => $newId, 'device_created' => $deviceCreated]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error in addRow: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra khi thêm dòng'], 500);
        }
    }

    /**
     * Xóa một dòng kế hoạch 2027; giữ nguyên thiết bị và dữ liệu tham khảo năm 2026.
     */
    public function deleteRow(): void
    {
        $input = $this->readJsonInput();
        $rawPlanId = $input['plan_id'] ?? null;
        $planId = is_string($rawPlanId) || is_int($rawPlanId)
            ? filter_var($rawPlanId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        if ($planId === false) {
            $this->jsonResponse(['success' => false, 'message' => 'Dòng kế hoạch không hợp lệ'], 422);
        }

        try {
            $this->db->beginTransaction();
            $params = [':id' => $planId, ':nam' => self::NAM_KE_HOACH];
            $sql = 'SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id = :id AND nam = :nam';
            $this->applyDonViFilter($sql, $params);
            $stmt = $this->db->prepare($sql . ' FOR UPDATE');
            $stmt->execute($params);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                $this->db->rollBack();
                $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy kế hoạch hoặc bạn không có quyền xóa'], 404);
            }

            $detailStmt = $this->db->prepare(
                'SELECT COUNT(*) FROM baoduong_dinhky_chitiet_iso WHERE kehoach_id = :id'
            );
            $detailStmt->execute([':id' => $planId]);
            if ((int)$detailStmt->fetchColumn() > 0) {
                $this->db->rollBack();
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Không thể xóa vì kế hoạch đã có chi tiết thực hiện liên quan',
                ], 409);
            }

            $deleteParams = [':id' => $planId, ':nam' => self::NAM_KE_HOACH];
            $deleteSql = 'DELETE FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id = :id AND nam = :nam';
            $this->applyDonViFilter($deleteSql, $deleteParams);
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute($deleteParams);
            if ($deleteStmt->rowCount() !== 1) {
                $this->db->rollBack();
                $this->jsonResponse(['success' => false, 'message' => 'Kế hoạch đã thay đổi, vui lòng tải lại trang'], 409);
            }

            logBaoDuongActivity($this->db, 'DELETE', (int)$planId, $plan, null);
            $this->db->commit();
            $this->jsonResponse(['success' => true, 'plan_id' => (int)$planId]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error in KeHoachBaoDuongNamTiepController::deleteRow: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa dòng kế hoạch'], 500);
        }
    }

    /**
     * Tạo thiết bị mới trong thietbi_iso (cùng cách các script import kế hoạch): mavt lấy theo tên,
     * thêm hậu tố nếu trùng khóa (mavt, somay, model).
     */
    private function createDevice(string $ten, string $serial, string $madv, string $ngayKtsd): int
    {
        $lengths = [];
        foreach (['mavt', 'tenvt', 'somay', 'mamay'] as $column) {
            $stmt = $this->db->prepare(
                'SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute(['thietbi_iso', $column]);
            $lengths[$column] = (int)($stmt->fetchColumn() ?: 0);
        }
        $fit = static fn(string $value, int $max): string => $max > 0 && mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;

        $tenvt = $fit($ten, $lengths['tenvt']);
        $somay = $fit($serial, $lengths['somay']);
        $exists = $this->db->prepare('SELECT 1 FROM thietbi_iso WHERE mavt = ? AND somay = ? AND model = ? LIMIT 1');
        $mavt = $fit($ten, $lengths['mavt']);
        for ($n = 1; $n <= 20; $n++) {
            $exists->execute([$mavt, $somay, '']);
            if (!$exists->fetchColumn()) {
                break;
            }
            $suffix = '-' . $n;
            $mavt = $fit($ten, max(1, ($lengths['mavt'] ?: 255) - strlen($suffix))) . $suffix;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO thietbi_iso (mavt, tenvt, somay, model, homay, dienap, thongtincb, loaidau, mucdau, madv, bdtime, ngayktsd, tlkt, hosomay, mamay)
             VALUES (:mavt, :tenvt, :somay, :model, :homay, :dienap, :thongtincb, :loaidau, :mucdau, :madv, :bdtime, :ngayktsd, :tlkt, :hosomay, :mamay)'
        );
        $stmt->execute([
            ':mavt' => $mavt, ':tenvt' => $tenvt, ':somay' => $somay, ':model' => '',
            ':homay' => '', ':dienap' => '', ':thongtincb' => '', ':loaidau' => '', ':mucdau' => '',
            ':madv' => $madv, ':bdtime' => 0, ':ngayktsd' => $ngayKtsd, ':tlkt' => '', ':hosomay' => '',
            ':mamay' => $fit(implode('-', array_filter([$mavt, $somay], static fn($p) => $p !== '')), $lengths['mamay']),
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function parseManufacturingYear(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('Năm SX phải là năm hợp lệ gồm 4 chữ số');
        }

        $year = trim((string)$value);
        if ($year === '') {
            return null;
        }
        if (!preg_match('/^\d{4}$/D', $year) || (int)$year < 1000 || (int)$year > 9999) {
            throw new InvalidArgumentException('Năm SX phải là năm hợp lệ gồm 4 chữ số');
        }

        return $year . '-01-01';
    }

    private function findThietbiId(string $ten, string $serial, bool $scoped = true): ?int
    {
        $sql = 'SELECT stt FROM thietbi_iso WHERE TRIM(tenvt) = :ten AND TRIM(somay) = :serial';
        $params = [':ten' => $ten, ':serial' => $serial];
        if ($scoped && !$this->isAdmin()) {
            $scope = $this->getMadvScope();
            if ($scope === []) {
                return null;
            }
            $in = [];
            foreach ($scope as $i => $value) {
                $in[] = ':madv' . $i;
                $params[':madv' . $i] = $value;
            }
            $sql .= ' AND TRIM(madv) IN (' . implode(',', $in) . ')';
        }
        $stmt = $this->db->prepare($sql . ' ORDER BY stt ASC LIMIT 1');
        $stmt->execute($params);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    private function insertPlan(array $data, array $quarters): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ke_hoach_bao_duong_dinh_ky_iso
             (thietbi_id, nam, ten_thietbi, so_serial, nhomsc, qui_1, qui_2, qui_3, qui_4, donvi_lam_chinh, donvi_lam_phu, ghi_chu, created_by)
             VALUES (:thietbi_id, :nam, :ten_thietbi, :so_serial, :nhomsc, :q1, :q2, :q3, :q4, :chinh, :phu, :ghi_chu, :created_by)"
        );
        $stmt->execute([
            ':q1' => $quarters[1], ':q2' => $quarters[2], ':q3' => $quarters[3], ':q4' => $quarters[4],
            ':thietbi_id' => (int)($data['thietbi_id'] ?? 0) > 0 ? (int)$data['thietbi_id'] : null,
            ':nam' => self::NAM_KE_HOACH,
            ':ten_thietbi' => $data['ten_thietbi'],
            ':so_serial' => $data['so_serial'] ?? '',
            ':nhomsc' => $data['nhomsc'] ?? '',
            ':chinh' => $data['donvi_lam_chinh'] ?? '',
            ':phu' => $data['donvi_lam_phu'] ?? '',
            ':ghi_chu' => $data['ghi_chu'] ?? '',
            ':created_by' => $_SESSION['user']['username'] ?? 'system',
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Lấy dòng kế hoạch năm kế tiếp theo ID; nếu chưa có thì tạo từ dòng 2026 tương ứng.
     * Cả hai đều bị giới hạn theo đơn vị của người dùng.
     */
    private function resolvePlan(int $planId, int $refId, ?array $quarters = null): ?array
    {
        if ($planId > 0) {
            return $this->findScopedPlan($planId, self::NAM_KE_HOACH);
        }
        if ($refId <= 0) {
            return null;
        }
        $ref = $this->findScopedPlan($refId, self::NAM_THAM_KHAO);
        if ($ref === null) {
            return null;
        }

        $newId = $this->insertPlan($ref, $quarters ?? [1 => '', 2 => '', 3 => '', 4 => '']);
        logBaoDuongActivity($this->db, 'INSERT', $newId, null, [
            'operation' => 'plan_next_year_create', 'nam' => self::NAM_KE_HOACH,
            'from_plan_id' => $refId, 'ten_thietbi' => $ref['ten_thietbi'], 'so_serial' => $ref['so_serial'] ?? '',
        ]);
        return $this->findScopedPlan($newId, self::NAM_KE_HOACH);
    }

    private function findScopedPlan(int $id, int $nam): ?array
    {
        $sql = 'SELECT * FROM ke_hoach_bao_duong_dinh_ky_iso WHERE id = :id AND nam = :nam';
        $params = [':id' => $id, ':nam' => $nam];
        $this->applyDonViFilter($sql, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Chuyển trạng thái 4 quý từ client thành [1..4 => 'TO'|''].
     */
    private function parseQuarters(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }
        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarters[$q] = !empty($raw[$q]) || !empty($raw[(string)$q]) ? self::PLAN_MARK : '';
        }
        return $quarters;
    }

    /**
     * Mặc định: quý nào đã hoàn thành (có dấu tích) năm 2026 thì chọn cho 2027.
     */
    private function defaultQuarters(?array $ref): array
    {
        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $quarters[$q] = $ref !== null && !empty($ref['qui_' . $q . '_hoantat']) ? self::PLAN_MARK : '';
        }
        return $quarters;
    }

    private function isQuarterSelected(array $plan, int $quarter): bool
    {
        return trim((string)($plan['qui_' . $quarter] ?? '')) !== '';
    }

    private function readJsonInput(): array
    {
        $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $expected = (string)($_SESSION['kehoach_hoso_csrf'] ?? '');
        if ($expected === '' || !hash_equals($expected, $token)) {
            $this->jsonResponse(['success' => false, 'message' => 'Phiên làm việc không hợp lệ, vui lòng tải lại trang'], 403);
        }
        $input = json_decode((string)file_get_contents('php://input'), true);
        return is_array($input) ? $input : [];
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
