<?php
declare(strict_types=1);

function banner_slots(): array{
    return [
        'home_hero'=>['label'=>'Ana Sayfa Hero','desktop'=>'1440 × 440 px','mobile'=>'768 × 900 px','ratio'=>'36:11'],
        'home_strip'=>['label'=>'Ana Sayfa İnce Kampanya','desktop'=>'1440 × 150 px','mobile'=>'768 × 260 px','ratio'=>'48:5'],
        'home_mid_left'=>['label'=>'Orta Alan Sol','desktop'=>'690 × 250 px','mobile'=>'720 × 300 px','ratio'=>'69:25'],
        'home_mid_right'=>['label'=>'Orta Alan Sağ','desktop'=>'690 × 250 px','mobile'=>'720 × 300 px','ratio'=>'69:25'],
        'category_top'=>['label'=>'Kategori Üst Banner','desktop'=>'1440 × 260 px','mobile'=>'768 × 520 px','ratio'=>'72:13'],
        'brand_top'=>['label'=>'Marka Üst Banner','desktop'=>'1440 × 260 px','mobile'=>'768 × 520 px','ratio'=>'72:13'],
    ];
}

function builtin_themes(): array{
    return [
        'standard'=>['name'=>'TOPLUCA Standart','description'=>'Turuncu, siyah ve beyaz kurumsal görünüm.','icon'=>'●'],
        'winter'=>['name'=>'Kış','description'=>'Soğuk mavi vurgular ve çok hafif kar dokusu.','icon'=>'❄'],
        'summer'=>['name'=>'Yaz','description'=>'Sıcak ve ferah yaz vurguları.','icon'=>'☀'],
        'ramadan'=>['name'=>'Ramazan / Bayram','description'=>'Altın tonlu, sade hilal ve ışık vurguları.','icon'=>'☾'],
        'republic'=>['name'=>'Cumhuriyet','description'=>'Kırmızı-beyaz, ölçülü 29 Ekim teması.','icon'=>'★'],
        'school'=>['name'=>'Okula Dönüş','description'=>'Eğitim dönemi için canlı fakat kurumsal tema.','icon'=>'✎'],
    ];
}

function active_theme_key(): string{
    static $theme=null;
    if($theme!==null)return $theme;
    $manual=(string)setting('active_theme','standard');
    if(setting('theme_auto_schedule','1')==='1'){
        try{
            $row=db()->query("SELECT theme_key FROM theme_schedules WHERE is_active=1 AND start_at<=NOW() AND end_at>=NOW() ORDER BY priority DESC,id DESC LIMIT 1")->fetch();
            if($row&&isset($row['theme_key']))$manual=(string)$row['theme_key'];
        }catch(Throwable $e){}
    }
    $all=builtin_themes();
    $theme=array_key_exists($manual,$all)?$manual:'standard';
    return $theme;
}
function theme_body_class(): string{return 'theme-'.active_theme_key();}

function site_logo_url(): string{
    $stored=trim((string)setting('site_logo',''));
    if($stored!==''&&is_file(dirname(__DIR__).'/'.$stored))return app_url($stored);
    $png=dirname(__DIR__).'/assets/img/topluca_logo.png';
    if(is_file($png))return app_url('assets/img/topluca_logo.png');
    return app_url('assets/img/topluca-logo.svg');
}
function site_favicon_url(): ?string{
    $stored=trim((string)setting('site_favicon',''));
    if($stored!==''&&is_file(dirname(__DIR__).'/'.$stored))return app_url($stored);
    return null;
}
function seo_value(string $key,string $fallback=''): string{$v=trim((string)setting($key,''));return $v!==''?$v:$fallback;}

function active_banners(string $position): array{
    $s=db()->prepare("SELECT * FROM banners WHERE position_code=? AND is_active=1 AND (start_at IS NULL OR start_at<=NOW()) AND (end_at IS NULL OR end_at>=NOW()) ORDER BY sort_order,id");
    $s->execute([$position]);return $s->fetchAll();
}
function active_home_sections(): array{try{return db()->query("SELECT * FROM homepage_sections WHERE is_active=1 ORDER BY sort_order,id")->fetchAll();}catch(Throwable $e){return [];}}
function current_theme_schedule(): ?array{if(setting('theme_auto_schedule','1')!=='1')return null;try{$r=db()->query("SELECT * FROM theme_schedules WHERE is_active=1 AND start_at<=NOW() AND end_at>=NOW() ORDER BY priority DESC,id DESC LIMIT 1")->fetch();return $r?:null;}catch(Throwable $e){return null;}}
function content_page_by_slug(string $slug): ?array{try{$s=db()->prepare('SELECT * FROM content_pages WHERE slug=? AND is_active=1 LIMIT 1');$s->execute([$slug]);$r=$s->fetch();return $r?:null;}catch(Throwable $e){return null;}}

function theme_inline_vars(): string{
    $accent=trim((string)setting('theme_accent_color',''));
    if($accent!==''&&preg_match('/^#[0-9a-fA-F]{6}$/',$accent))return '--orange:'.$accent.';';
    return '';
}

function banner_style(array $banner): string{
    $text=trim((string)($banner['text_color']??'#ffffff'));if(!preg_match('/^#[0-9a-fA-F]{6}$/',$text))$text='#ffffff';
    $overlay=trim((string)($banner['overlay_color']??'#000000'));if(!preg_match('/^#[0-9a-fA-F]{6}$/',$overlay))$overlay='#000000';
    $opacity=max(0,min(.85,(float)($banner['overlay_opacity']??.20)));
    return '--banner-text:'.$text.';--banner-overlay:'.$overlay.';--banner-opacity:'.$opacity.';';
}

function storefront_link(?string $url,string $fallback='#'): string{
    $url=trim((string)$url);if($url==='')return $fallback;
    if(preg_match('~^https?://~i',$url))return $url;
    if(strpos($url,'#')===0)return $url;
    return app_url($url);
}
