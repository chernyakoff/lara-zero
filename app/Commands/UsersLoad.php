<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;
use libphonenumber\PhoneNumberUtil;
use App\Models\User;
/*



function parsePhone($phone)
{
	global $phoneUtil;
	try {
		$info = $phoneUtil->parse($phone, "RU");
		if ($phoneUtil->isValidNumber($info)) {
			return $phoneUtil->format($info, \libphonenumber\PhoneNumberFormat::E164);
		} else {
			return false;
		}
	} catch (\libphonenumber\NumberParseException $e) {
		return false;
	}
}

$csv = Reader::createFromPath('./leads.csv', 'r');
$csv->setHeaderOffset(0); //set the CSV header offset
$csv->setDelimiter(';');


$records = Statement::create()->process($csv);
$phones = [];
foreach ($records->getRecords() as $record) {
	$phones = array_merge($phones, fetchPhones($record));
}

foreach ($phones as $phone) {


}
*/

class UsersLoad extends Command
{
    private $columns = [
        'Рабочий телефон',
        'Мобильный телефон',
        'Домашний телефон',
        'Телефон для рассылок',
        'Другой телефон'
    ];

    private $phoneUtil;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'users:load';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Создает список пользователей с телефоном из CSV файла';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(PhoneNumberUtil $phoneUtil)
    {
        $this->phoneUtil = $phoneUtil;
        $csv = Reader::createFromPath(Storage::path("leads.csv"), 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(';');
        $records = Statement::create()->process($csv);
        foreach ($records->getRecords() as $record) {
            foreach($this->fetchPhones($record) as $phone) {
                try {
                    User::Create(['phone' => $phone]);
                    Log::info('Пользователь добавлен: '.$phone);
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function fetchPhones(array $record): array
    {
        $phones = [];
        foreach ($this->columns as $col) {
            $value =  $record[$col];
            if (!empty($value)) {
                if (strpos($value, ',') !== false) {
                    foreach (explode(',', $value) as $subvalue) {
                        if ($phone = $this->parsePhone($subvalue)) {
                            array_push($phones, $phone);
                        }
                    }
                } else {
                    if ($phone = $this->parsePhone($value)) {
                        array_push($phones, $phone);
                    }
                }
            }
        }
        return $phones;
    }

    private function parsePhone($phone)
    {
        try {
            $info = $this->phoneUtil->parse($phone, "RU");
            if ($this->phoneUtil->isValidNumber($info)) {
                return $this->phoneUtil->format($info, \libphonenumber\PhoneNumberFormat::E164);
            } else {
                return false;
            }
        } catch (\libphonenumber\NumberParseException $e) {
            return false;
        }
    }
}
