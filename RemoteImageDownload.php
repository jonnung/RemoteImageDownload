<?php
/**
 * RemoteImageDownload  <img>태그 안에 파일 url을 추출하여 자동 다운로드
 * @author EunwooCho (jonnung@gmail.com)
 * @version 1.0
 * @date 2013-11-16
 */

class RemoteImageDownload
{
    private $sFindUrl = 'http://';  // 찾을 URL 호스트
    private $aSaveUrl = array();    // 찾은 원격지 대상 파일 URL
    private $aFileExtension = array('jpg', 'gif', 'png', 'JPG', 'GIF', 'PNG');  // 파일 확장자
    private $iMatchCount;   // 찾은 URL 갯수

    public  $sReplaceUrl = '/download'; // 치환할 URL    
    public  $sSaveRootDir = ''; // 파일이 저장 될 루트 디렉토리
    

    /**
     * __construct 
     * @param String $sUrl          찾을 URL
     * @param String $sRootDir  파일이 저장 될 루트 디렉토리
     */
    public function __construct($sUrl, $sRootDir='')
    {
        if ($sUrl == '' || isset($sRootDir) == false) {
            echo 'URL과 디렉토리 지정은 필수 입니다.';
            exit;
        } else {
            $this->sFindUrl = $sUrl;
            $this->sSaveRootDir = $sRootDir;
        }         
    }

    /**
     * setUrlAnalyzeInText  텍스트 안에서 찾고자 하는 URL을 패턴을 추출
     * @param String $sText
     * @comment 리턴 없음, private 멤버 변수에 바로 저장
     */
    private function setUrlAnalyzeInText ($sText)
    {
        unset ($this->aSaveUrl);        
        $sUrlEscapeSlashes = str_replace('/', '\/', $this->sFindUrl);   // 정규표현식에 사용하기 위해 슬래시를 escape
        $sFileExtension = implode('|', $this->aFileExtension);  // 파일 확장자 조합

        // $sPatten = "/(src|SRC)[^>]*(?P<target_url>". $sUrlEscapeSlashes ."[^>]+\.(". $sFileExtension ."))/";
        $sPatten = "/(?P<target_url>". $sUrlEscapeSlashes ."[^>]+\.(". $sFileExtension ."))/";


        if ($this->iMatchCount = preg_match_all($sPatten, $sText, $aMatches)) {            
            foreach ($aMatches['target_url'] as $sUrl) {
                $this->aSaveUrl[] = array('origin' => $sUrl);
            }
        }
    }

    /**
     * setParseUrlFilePath 파일 URL로부터 파일명과 디렉토리 구조를 분리
     * @param String $sOriginUrl   원격지 URL  
     * @param String $sFileName  파일명
     * @param Array  $aFilePath    디렉토리 구조
     */
    private function setParseUrlFilePath ($sOriginUrl, &$sFileName, &$aFilePath)
    {
        $sParseUrl = parse_url($sOriginUrl, PHP_URL_PATH);
        $aFilePath = explode('/', $sParseUrl);
        $sFileName = array_pop($aFilePath);

        if ($aFilePath[0] == '') {
            array_shift($aFilePath);
        }
    }

    /**
     * setDirectory 파일이 저장 될 디렉토리 생성
     * @param Array $aPath  디렉토리 구조
     */
    private function setDirectory (&$aPath)
    {
        $sDirectory = $this->sSaveRootDir;

        foreach ($aPath as $sPath) {

            $sDirectory .= $sPath .'/';

            if (is_dir($sDirectory) == false) {
                if (mkdir($sDirectory) == false) {
                    die ($sDirectory .'디렉토리를 생성할 수 없습니다.');
                }
            }
        }
        // 파일이 저장 될 루트 디렉토리가 지정되어 있으면 디렉토리 구조에 추가
        if ($this->sSaveRootDir != '') array_unshift($aPath, $this->sSaveRootDir);
    }

    /**
     * getDownloadExecute 원격지 파일 다운로드
     * @param  String $sOriginUrl   원격지 파일 URL
     * @param  String $sFileName  파일명
     * @param  Array  $aFilePath     디렉토리 구조
     * @return Bool   성공 여부
     * @comment  다운로드 성공 여부를 return 하도록 수정
     * @todo     PHP5 이하 에서 동작 할 수 있드록 CURL 모듈을 이용한 기능 추가 필요 / UNIX 환경의 wget CLI 명령어 활용 가능
     */
    private function getDownloadExecute ($sOriginUrl, $sFileName, $aFilePath)
    {
        if (function_exists('file_put_contents')) {

            $sFileFullPath = implode('/', $aFilePath) .'/'. $sFileName;

            if (is_file($sFileFullPath) == false) {

                $rGetContent = file_get_contents($sOriginUrl);

                if ($rGetContent == true) {
                    return file_put_contents($sFileFullPath, $rGetContent);    // PHP5 >
                } else {
                    echo 'Can not get file! > '. $sOriginUrl . chr(10);                    
                    return false;
                }
            }
        } else {
            die ('file_put_contents 함수를 사용 할 수 없습니다. PHP5 환경에서 정상 동작합니다.');
        }
    }

    /**
     * getRemoteFile 원격지 파일 다운로드 컨트롤
     * @param   String $sText
     * @return  Array 원격지 파일 URL과 치환 된 파일 URL
     */
    public function getRemoteFile ($sText)
    {
        $this->setUrlAnalyzeInText($sText);

        if (is_array($this->aSaveUrl) == true) {
            foreach ($this->aSaveUrl as $index => $aUrl) {

                $sOriginUrl = $aUrl['origin'];  // 원격지 파일 URL
                $sFileName; // 파일명
                $aFilePath = array();   // 디렉토리 구조

                $this->setParseUrlFilePath($sOriginUrl, $sFileName, $aFilePath);  // 파일명과 디렉토리 구조 분리
                $this->setDirectory($aFilePath);  // 로컬 디렉토리 생성
                
                $bExecuteResult = $this->getDownloadExecute($sOriginUrl, $sFileName, $aFilePath);  // 파일 다운로드 실행
                if ($bExecuteResult !== false) {
                    $this->aSaveUrl[$index]['local'] = str_replace($this->sFindUrl, $this->sReplaceUrl, $sOriginUrl);  // 파일 URL 치환
                }
            }
            return $this->aSaveUrl;
        }
    }
}
