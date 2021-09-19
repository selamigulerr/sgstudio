<?php
include("../_ayarlar.php");
include(PROTOKOLLER."pro_yukleyici.php");
#---------------------------------------------------------------------------------------------------[ TEMPLATE MOTORU SMARTY YAPISINI ÇALIŞTIRAN PROTOKOL YÜKLENİYOR. ]-----#
$pro_smarty = pro_smarty::Tekil();
$pro_smarty->SablonYolu("pys");
#-------------------------------------------------------------------------------------------------------------------------------------[ DİL FONKSİYONLARI YÜKLENİYOR. ]-----#
if(! $_SESSION["projedil"]){$_SESSION["projedil"]="en";}
$pro_smarty->yayinla("projedil", $_SESSION["projedil"]);
if($_SESSION["admin"]){
	$pro_smarty->yayinla("admin", $_SESSION["admin"]);
}
#----------------------------------------------------------------------------------------------------------------------------------------[ PROJE AYARLARI YÜKLENİYOR. ]-----#
$ayarlar = cys_ayarlar_protokoller::ayarlar('pys');
$pro_smarty->yayinla("ayarlar", $ayarlar);
#----------------------------------------------------------------------------------------------------------------------------------------[ PROJE MENÜLERİ YÜKLENİYOR. ]-----#
$menuler = cys_menuelemanlari_fonksiyonlar::MenuPYS();
$pro_smarty->yayinla("menuler", $menuler);
$pro_smarty->yayinla("anayol", ANAYOL);
#--------------------------------------------------------------------------------------------------------------------------------[ PROJE STATİK İÇERİKLER YÜKLENİYOR. ]-----#
$statikicerikler = iys_statikicerikler_protokoller::statikicerikler();
$pro_smarty->yayinla("statikicerikler", $statikicerikler);
#-----------------------------------------------------------------------------------------------------------------------------------[ GET PARAMETRELERİ KARŞILANIYOR. ]-----#
$sayfaseo = $_GET["sayfaseo"];
$get_deger1 = $_GET["deger1"];
$get_deger2 = $_GET["deger2"];
$get_deger3 = $_GET["deger3"];
$get_deger4 = $_GET["deger4"];
#----------------------------------------------------------[ YERLEŞİM SINIFI VE YERLEŞİM MODUL BİLGİLERİ ALINIYOR ve MODUL SINIFLARI ÇAĞIRILIYOR ]-----#
include(ANAYOL."pys/yerlesim/index.php");
$yerlesimsinif = new yerlesim();
$yerlesimsinif->pys();
#--------------------------------------------------------------------------------------------------------------------------[ ÖNSAYFA VE ANASAYFA YAPISI BELİRLENİYOR. ]-----#
if(! $sayfaseo){$sayfaseo = $ayarlar["acilissayfasi"];}
#--------------------------------------------------------------------------------------------------------------------------------------------[ SAYFA BİLGİSİ ALINIYOR ]-----#
$parametre = array();
$parametre["where"] = "sayfaseo='$sayfaseo' AND durum='1'";
$sayfayapisi = pro_db::Select("iys_sayfalar", $parametre);
$sayfayapisi = $sayfayapisi[0];
$pro_smarty->yayinla("sayfayapisi", $sayfayapisi);
if($sayfayapisi){
#--------------------------------------------------------------------------------------------------------------------------------------------[ SINIF BİLGİSİ ALINIYOR ]-----#
	$parametre = array();
	$parametre["where"] = "sinifseo='{$sayfayapisi["sinifseo"]}' AND durum='1' AND mimari='pys'";
	$sinifyapisi = pro_db::Select("cys_siniflar", $parametre);
	$sinifyapisi = $sinifyapisi[0];
	$pro_smarty->yayinla("sinifyapisi", $sinifyapisi);
	if($sinifyapisi["sinifseo"]){
#-------------------------------------------------------------------------------------------------------------[ METOT BİLGİSİ Get Değer 1 Parametresine Göre ALINIYOR ]-----#
		if($get_deger1){
			$parametre = array();
			$parametre["where"] = "sinifid='{$sinifyapisi["id"]}' AND metotseo='{$get_deger1}' AND durum='1'";
			$metotyapisibilgi = pro_db::Select("cys_metotlar", $parametre);
		}
#------------------------------------------------------------------------------------------------------------------------[ METOT BİLGİSİ Sayfa Temasına Göre ALINIYOR ]-----#
		if($metotyapisibilgi){
			$metotyapisi = $metotyapisibilgi[0];
		}else{
			$parametre = array();
			$parametre["where"] = "sinifid='{$sinifyapisi["id"]}' AND metotturu='3' AND durum='1'";
			$metotyapisi = pro_db::Select("cys_metotlar", $parametre);
			$metotyapisi = $metotyapisi[0];
		}
		$pro_smarty->yayinla("metotyapisi", $metotyapisi);
		if($metotyapisi){
#-----------------------------------------------------------------------------------------------------[ SAYFA MODUL BİLGİLERİ ALINIYOR ve MODUL SINIFLARI ÇAĞIRILIYOR ]-----#
			$parametre = array();
			$parametre["where"] = "sayfaid='{$sayfayapisi["id"]}' AND sayfametotid='{$metotyapisi["id"]}' AND durum='1' AND calistir='1'";
			$parametre["orderby"] = "sira";
			$modullerdata = pro_db::Select("iys_moduller", $parametre);
			
			for($m=0; $m<count($modullerdata); $m++){
				$modullerdata[$m]["metotparametreleri"] = unserialize($modullerdata[$m]["metotparametreleri"]);
				if(file_exists(ANAYOL."pys/".$modullerdata[$m]["sinifseo"]."/index.php")){
					require_once(ANAYOL."pys/".$modullerdata[$m]["sinifseo"]."/index.php");
					if(class_exists($modullerdata[$m]["sinifseo"])){
						$modulsinif[$modullerdata[$m]["sinifseo"]] = new $modullerdata[$m]["sinifseo"]();
						if(method_exists($modulsinif[$modullerdata[$m]["sinifseo"]], $modullerdata[$m]["metotseo"])){
							$modullerdata[$m]["modulicerik"] = $modulsinif[$modullerdata[$m]["sinifseo"]]->$modullerdata[$m]["metotseo"]($modullerdata[$m], $get_deger1, $get_deger2, $get_deger3, $get_deger4);
							if(file_exists(ANAYOL."pys/".$modullerdata[$m]["sinifseo"]."/bilesenler/".$modullerdata[$m]["metotseo"].".tpl")){
								$modullerdata[$m]["sablon"] = ANAYOL."pys/".$modullerdata[$m]["sinifseo"]."/bilesenler/".$modullerdata[$m]["metotseo"].".tpl";
							}else{$modullerdata[$m]["hata"] = "<strong>".$modullerdata[$m]["modul"]."</strong> isimli modülün <strong>".$modullerdata[$m]["metotseo"]."</strong> isimli tpl dosyası dizimde bulunamadı.";}
						}else{$modullerdata[$m]["hata"] = "<strong>".$modullerdata[$m]["modul"]."</strong> isimli modülün <strong>".$modullerdata[$m]["metotseo"]."</strong> isimli metotdu bulunamadı.";}
					}else{$modullerdata[$m]["hata"] = "<strong>".$modullerdata[$m]["modul"]."</strong> isimli modülün <strong>(".$modullerdata[$m]["sinifseo"].")</strong> isimli sınıfı bulunamadı.";}
				}else{$modullerdata[$m]["hata"] = "<strong>".$modullerdata[$m]["modul"]."</strong> isimli modülün <strong>(".$modullerdata[$m]["sinifseo"].")</strong> isimli sınıf klasörü dosya diziminde bulunamadı.";}
				if($modullerdata[$m]["hata"]){$modullerdata[$m]["sablon"] = ANAYOL."hys/bilesenler/modul.tpl";}
				$moduller[$modullerdata[$m]["sayfametottemaseo"]][] = $modullerdata[$m];
			}
			$pro_smarty->yayinla("moduller", $moduller);
			if($metotyapisi["metotyapisi"]==1){
#------------------------------------------------------------------------------------------------------------------------------------------[ SAYFA SINIFI ÇAGIRILIYOR ]-----#
				if(file_exists(ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/index.php")){
					require_once(ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/index.php");
					if(class_exists($sinifyapisi["sinifseo"])){
						$sinif = new $sinifyapisi["sinifseo"]();
						if(method_exists($sinif, $metotyapisi["metotseo"])){
							$sinif->$metotyapisi["metotseo"]($sinifyapisi, $metotyapisi, $sayfayapisi, $get_deger1, $get_deger2, $get_deger3, $get_deger4);
							if(file_exists(ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/bilesenler/".$metotyapisi["metotseo"].".tpl")){
								$pro_smarty->HtmlSablon(ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/bilesenler/".$metotyapisi["metotseo"]);
							}else{$error["content"] = "Metot tasarım dosyası yok...! Metot tasarım dosyası : ".ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/bilesenler/".$metotyapisi["metotseo"];}
						}else{$hata["icerik"] = "Metot tanımı yok...! Metot seo : ".$metotyapisi["metotseo"];}
					}else{$hata["icerik"] = "Sınıf tanımı yok...! Sınıf seo : ".$sinifyapisi["sinifseo"];}
				}else{$hata["icerik"] = "Sınıf dosyası yok...! Dosya : ".ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/index.php";}
			}elseif($metotyapisi["metotyapisi"]==2){
				include(ANAYOL."cys/yorumlayici/pysindex.php");
				$sinif = new yorumlayici("yorumlayici", $metotyapisi["sablonseo"]);
				$sinif->$metotyapisi["sablonseo"]($sinifyapisi, $metotyapisi, $get_deger1, $get_deger2, $get_deger3, $get_deger4, $get_deger5);
				$pro_smarty->HtmlSablon(ANAYOL.$sinifyapisi["mimari"]."/".$sinifyapisi["sinifseo"]."/bilesenler/".$metotyapisi["metotseo"]);
			}
		}else{$hata["icerik"] = "Metot bulunamadı...! Metot seo : ".$get_deger1;}
	}else{$$hata["icerik"] = "Sınıf bulunamadı...! Sınıf seo : ".$sayfayapisi["sinifseo"];}
}else{$hata["icerik"] = "Sayfa bulunamadı...! Sayfa seo : ".$sayfaseo;}
if($hata){
	$pro_smarty->yayinla("hata", $hata);
	$pro_smarty->HtmlSablon(ANAYOL."/cekirdek/hata/hata");
}

<a href="tel:+902163363252">+90 216 336 32 52</a>