<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\FingerLog;
use Log;


class iclockController extends Controller
{

    public function __invoke(Request $request)
    {

    }

    // handshake
    public function handshake(Request $request)
    {
        $data = [
            'url' => json_encode($request->all()),
            'data' => $request->getContent(),
            'sn' => $request->input('SN'),
            'option' => $request->input('option'),
        ];
        DB::table('device_log')->insert($data);

        // update status device
        DB::table('devices')->updateOrInsert(
            ['no_sn' => $request->input('SN')],
            ['online' => now()]
        );

        $r = "GET OPTION FROM: {$request->input('SN')}\r\n" .
            "Stamp=9999\r\n" .
            "OpStamp=" . time() . "\r\n" .
            "ErrorDelay=60\r\n" .
            "Delay=30\r\n" .
            "ResLogDay=18250\r\n" .
            "ResLogDelCount=10000\r\n" .
            "ResLogCount=50000\r\n" .
            "TransTimes=00:00;14:05\r\n" .
            "TransInterval=1\r\n" .
            "TransFlag=1111000000\r\n" .
            //  "TimeZone=7\r\n" .
            "Realtime=1\r\n" .
            "Encrypt=0";

        return $r;
    }

    public function receiveRecords(Request $request)
    {
        $maxLength = 6550;
        $jsonData = json_encode($request->all());
        if (strlen($jsonData) > $maxLength) {
            $jsonData = substr($jsonData, 0, $maxLength);
        }
        // Log the incoming request
        FingerLog::create([
            'url' => json_encode($request->all()),
            'data' => $jsonData,
        ]);

        try {
            $arr = preg_split('/\\r\\n|\\r|\\n/', $request->getContent());
            $tot = 0;
            $attendances = [];
            $errors = [];
            foreach ($arr as $record) {
                if (empty($record)) {
                    continue;
                }
                $data = explode("\t", $record);
                if (!empty($data) && isset($data[0]) && is_numeric($data[0])) {
                    $employeeId = $data[0];
                    Employee::firstOrCreate(
                        ['employee_id' => $employeeId],
                        ['name' => '']
                    );
                    $timestamp = $data[1] ?? ' ';
                    $status1 = $this->validateAndFormatInteger($data[2]) ?? -1;
                    $sn = $request->input('SN') ?? ' ';

                    // Check if the record already exists
                    $existingRecord = DB::table('attendances')
                        ->where('employee_id', $employeeId)
                        ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, timestamp, ?)) <= 5', [$timestamp])
                        ->where('status1', $status1)
                        ->where('sn', $sn)
                        ->first();

                    if ($existingRecord) {
                        $errors[] = 'Duplicate record: ' . $record;
                        continue;
                    }

                    $attendances[] = [
                        'sn' => $sn,
                        'table' => $request->input('table') ?? ' ',
                        'stamp' => $request->input('Stamp') ?? ' ',
                        'employee_id' => $employeeId,
                        'timestamp' => $timestamp,
                        'status1' => $status1,
                        'status2' => $this->validateAndFormatInteger($data[3]) ?? -1,
                        'status3' => $this->validateAndFormatInteger($data[4]) ?? -1,
                        'status4' => $this->validateAndFormatInteger($data[5]) ?? -1,
                        'status5' => $this->validateAndFormatInteger($data[6]) ?? -1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $tot++;
                } else {
                    $errors[] = 'Invalid or incomplete data: ' . $record;
                }
            }

            // Perform batch insert
            if (!empty($attendances)) {
                DB::table('attendances')->insert($attendances);
            }

            // Log errors if any
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    Log::info($error);
                }
            }

            return "OK: " . $tot; // Success response
        } catch (\Exception $e) {
            $errorData = [
                'data' => $e->getMessage() . '::Line::' . $e->getLine(),
                'created_at' => now(),
                'updated_at' => now()
            ];
            DB::table('error_log')->insert($errorData);
            Log::error($e);
            return "ERROR: 0\n";
        }
    }

    public function test(Request $request)
    {
        $log['data'] = $request->getContent();
        DB::table('finger_log')->insert($log);
    }


    public function getrequest(Request $request)
    {
        try {
            // Perform the update or insert operation
            DB::table('devices')->updateOrInsert(
                ['no_sn' => $request->input('SN')],
                ['online' => now()]
            );

        } catch (\Exception $e) {
            Log::error($e);
        }
        return "OK";
    }

    private function validateAndFormatInteger($value)
    {
        return isset($value) && $value !== '' ? (int) $value : null;
    }

}