<?php
require_once('../RemoteImageDownload.php');

$sFile = 'sample.csv';  // ������ ���ϸ�
$sFindUrl = 'http://jonnung.cafe24.com';   // ã�� URL ���̽�
$sSaveRootDir = 'download/';        // �ݵ�� �̸� ���� �Ǿ� �־����
$sLogFile = 'download_result.csv';  // ��� ����Ʈ ����

$oRemoteImage = new RemoteImageDownload($sFindUrl, $sSaveRootDir);
$iRoofCount = 0;
$oFileHandle = fopen($sFile, 'r');
while(feof($oFileHandle) == false)
{

    $aCsvLine = fgetcsv($oFileHandle);

    $iProductCode = $aCsvLine[0];  // ���а�
    $sProductDesc = $aCsvLine[1];  // img �±װ� ���� �� �ؽ�Ʈ

    $aResult = $oRemoteImage->getRemoteFile($sProductDesc);

    if (is_array($aResult) == true) {

        $oLogfileHandle = fopen($sLogFile, 'a');

        foreach ($aResult as $key => $aURL) {

            if (isset($aURL['local']) == true) {
                $sLog = $iProductCode .','. $aURL['origin'] .','. $aURL['local'] .',T'. chr(10);
            } else {
                $sLog = $iProductCode .','. $aURL['origin'] .',,F'. chr(10);
            }

            fwrite($oLogfileHandle, $sLog);
        }
        fclose($oLogfileHandle);
    }
    $iRoofCount++;
    if ($iRoofCount == 10000) {
        continue 1;
    }
}

fclose($oFileHandle);