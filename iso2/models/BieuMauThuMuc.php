<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class BieuMauThuMuc extends BaseModel
{
    public function __construct()
    {
        parent::__construct('bieu_mau_thu_muc');
    }

    public function allOrdered(): array
    {
        return $this->getAll('ORDER BY ten_thu_muc ASC, id ASC');
    }
}
