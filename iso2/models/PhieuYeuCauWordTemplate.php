<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class PhieuYeuCauWordTemplate extends BaseModel
{
    public function __construct()
    {
        parent::__construct('phieuyeucau_word_template');
    }

    public function current(): array|false
    {
        return $this->find(1);
    }

    public function replace(array $data): void
    {
        $current = $this->current();
        if ($current) {
            $this->update(1, $data);
            return;
        }

        $data['id'] = 1;
        $this->create($data);
    }
}