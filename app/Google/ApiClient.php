<?php


namespace App\Google;


class ApiClient
{

    public static function getA1Cols(array $cols): array
    {
        return array_combine($cols, array_slice(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, count($cols)));
    }

    public function createSpreadSheet(
        string $title,
        array $grid = null,
        \Google_Service_Drive_Permission $permission = null
    ): \Google_Service_Sheets_Spreadsheet
    {
        $client = $this->getClient();

        $googleSheets = new \Google_Service_Sheets($client);
        $spreadsheet = $googleSheets->spreadsheets->create(
            new \Google_Service_Sheets_Spreadsheet(['properties' => ['title' => $title]]),
            ['fields' => implode(',', ['spreadsheetId', 'spreadsheetUrl'])]
        );

        if ($grid) {
            $valueRange = new \Google_Service_Sheets_ValueRange();
            $valueRange->setValues($this->replaceNullValues($grid));
            $valueRange->setRange('A1');

            $body = new \Google_Service_Sheets_BatchUpdateValuesRequest();
            $body->setData($valueRange);
            $body->setValueInputOption('RAW');
            $googleSheets->spreadsheets_values->batchUpdate($spreadsheet->getSpreadsheetId(), $body);
        }
        if ($permission) {
            $googleDrive = new \Google_Service_Drive($client);
            $googleDrive->permissions->create($spreadsheet->getSpreadsheetId(), $permission);
        }

        return $spreadsheet;
    }

    public function getCells(string $spreadsheetId, string $range): array
    {
        $googleSheets = new \Google_Service_Sheets($this->getClient());
        $range = $googleSheets->spreadsheets_values->get($spreadsheetId, $range);
        $values = $range->getValues();
        $headers = array_shift($values);

        $rows = [];
        foreach ($values as $i => $cols) {
            $nulls = array_fill(0, count($headers), null);
            $rows[] = array_combine($headers, $cols + $nulls);
        }

        return $rows;
    }

    public function writeCells(string $spreadsheetId, string $range, array $data)
    {
        $googleSheets = new \Google_Service_Sheets($this->getClient());

        $values = new \Google_Service_Sheets_ValueRange();
        $values->setRange($range);
        $values->setValues(array_map('array_values', $data));

        $request = new \Google_Service_Sheets_BatchUpdateValuesRequest();
        $request->setValueInputOption('RAW');
        $request->setData($values);

        $googleSheets->spreadsheets_values->batchUpdate($spreadsheetId, $request);
    }

    private function getClient(): \Google_Client
    {
        $client = new \Google_Client();
        $client->setApplicationName('ASDARK');
        $client->setAuthConfig(getenv('GOOGLE_SERVICE_ACCOUNT_CREDENTIALS_FILE'));
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        $client->addScope(\Google_Service_Sheets::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        return $client;
    }

    private function replaceNullValues(array $grid): array
    {
        foreach ($grid as $i => $row) {
            foreach ($row as $j => $value) {
                $grid[$i][$j] = is_null($value) ? '' : $value;
            }
        }

        return $grid;
    }
}
