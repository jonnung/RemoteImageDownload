<?php
require_once('../RemoteImageDownload.php');

$sFile = 'sample.csv';  // 데이터 파일명
$sFindUrl = 'http://jonnung.cafe24.com';   // 찾을 URL 베이스
$sSaveRootDir = 'download/';        // 반드시 미리 생성 되어 있어야함
$sLogFile = 'download_result.csv';  // 결과 리포트 파일

$oRemoteImage = new RemoteImageDownload($sFindUrl, $sSaveRootDir);
$iRoofCount = 0;
$oFileHandle = fopen($sFile, 'r');
while(feof($oFileHandle) == false)
{

    $aCsvLine = fgetcsv($oFileHandle);

    $iProductCode = $aCsvLine[0];  // 구분값
    $sProductDesc = $aCsvLine[1];  // img 태그가 포함 된 텍스트

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