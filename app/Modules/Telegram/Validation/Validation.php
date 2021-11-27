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
     */
    public function attributes(string $attribute)
    {
        $this->text = $attribute;

        return $this;
    }

    /**
     * @param mixed $rule
     * @param ...$rules
     */
    public function check($rule, ...$rules): Validation
    {
        if (!is_array($rule)) {
            $check_rule = explode(':', $rule);
            $method = $check_rule[0];
            $this->$method(($check_rule[1] ?? null));
        }
        return $this;
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
            array_push($this->error_details, [
                __('Boshqa ma\'lumot kiriting')
            ]);
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

    public function name(): Validation
    {
        if (preg_match("/[^A-Za-zА-Яа-яЁё\s]/u", $this->text)) {
            $this->is_failed = true;
            array_push($this->error_details, [
                __('Ism(familiya)ngizni to\'g\'ri kiriting')
            ]);
        }

        return $this;
    }

    public function regex(?string $check = null)
    {
        if (is_null($check) || !preg_match($check, $this->text)) {
            $this->is_failed = true;
            array_push($this->error_details, [
                __('T\'g\'ri namuna asosida kiriting')
            ]);
        }
    }
}
