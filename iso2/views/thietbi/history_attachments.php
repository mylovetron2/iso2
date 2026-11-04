<?php
$files = $historyAttachments[$historyDossierId] ?? [];
?>
<?php if ($files): ?>
    <div class="mt-3 border-t pt-3">
        <div class="text-base font-semibold text-blue-700 mb-2">
            <i class="fas fa-paperclip mr-1"></i>Ảnh &amp; PDF (<?= count($files) ?>)
        </div>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($files as $file):
                $attachmentUrl = appUrl('hososcbd_repair_details.php') . '?' . http_build_query([
                    'id' => $historyDossierId,
                    'repair_attachment' => $file['name'],
                ]);
            ?>
                <a href="<?= htmlspecialchars($attachmentUrl, ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank" rel="noopener"
                   class="flex items-center gap-2 border rounded p-2 text-blue-700 hover:bg-blue-50"
                   style="max-width:100%;">
                    <?php if ($file['is_pdf']): ?>
                        <i class="fas fa-file-pdf text-red-600 text-2xl" aria-hidden="true"></i>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($attachmentUrl, ENT_QUOTES, 'UTF-8') ?>"
                             alt="<?= htmlspecialchars($file['original'], ENT_QUOTES, 'UTF-8') ?>"
                             loading="lazy" class="rounded"
                             style="width:80px;height:80px;object-fit:cover;">
                    <?php endif; ?>
                    <span class="text-sm break-all">
                        <?= $file['is_pdf'] ? 'PDF: ' : '' ?><?= htmlspecialchars($file['original'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
