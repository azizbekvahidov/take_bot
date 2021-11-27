<?php


namespace App\Modules\Telegram\Validation;


class Validation
{

    /**
     * @var string
     */
    private $text;

    /**
     * @var bool
     */
    private $is_failed = false;

    /**
     * @var array
     */
    private $error_details = [];

    /**
     * Validation constructor.
     * @param string|null $text
     */
    public function __construct(?string $text = null)
    {
        $this->text = $text;
    }

    /**
     * @param string $attribute
     * @return Validation
     */
    public function attributes(string $attribute): Validation
    {
        $this->text = $attribute;

        return $this;
    }

    /**
     * @param string $rule
     * @param ...$rules
     * @return Validation
     */
    public function check(string $rule, ...$rules): Validation
    {
        $check_rule = explode(':', $rule);
        $method = $check_rule[0];
        $this->$method(($check_rule[1] ?? null));
        foreach ($rules as $rule) {
            $check_rule = explode(':', $rule);
            $method = $check_rule[0];
            $this->$method(($check_rule[1] ?? null));
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function fails(): bool
    {
        return $this->is_failed;
    }

    /**
     * @return array
     */
    public function details(): array
    {
        return $this->error_details;
    }


    /**
     * @param string|null $check
     * @return Validation
     */
    private function in(?string $check = null): Validation
    {
        $check_list = is_null($check) ? [] : explode(",", $check);

        if (!in_array($this->text, $check_list) || empty($check_list)) {
            $this->is_failed = true;
            array_push($this->error_details, __('Boshqa ma\'lumot kiriting'));
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function name(): Validation
    {
        if (preg_match("/[^A-Za-zА-Яа-яЁё\s]/u", $this->text)) {
            $this->is_failed = true;
            array_push($this->error_details, __('Ism(familiya)ngizni to\'g\'ri kiriting'));
        }

        return $this;
    }

    /**
     * @param string|null $check
     * @return Validation
     */
    public function regex(?string $check = null): Validation
    {
        if (is_null($check) || !preg_match($check, $this->text)) {
            $this->is_failed = true;
            array_push($this->error_details, __('To\'g\'ri namuna asosida kiriting'));
        }

        return $this;
    }

    /**
     * @param string|null $check
     * @return Validation
     */
    private function isContact(?string $check = null): Validation
    {
        if (!boolval($check)) {
            $this->is_failed = true;
            array_push($this->error_details, __('Iltimos, "Raqamni ulashish" tugmasini bosing'));
        }

        return $this;
    }
}
