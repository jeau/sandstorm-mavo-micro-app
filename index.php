<?php
require_once('include/php/spyc.php');

$sandstormHeaders = getallheaders();

if (!empty($sandstormHeaders['X-Sandstorm-User-Id'])) {
    $cmd = '/opt/app/sandstorm-integration/bin/getPublicId';
    $cmd = $cmd . ' ' . $sandstormHeaders['X-Sandstorm-Session-Id'];
    $publishingInfo = [];
    $output = exec($cmd, $publishingInfo);
    $isPublic = (file_exists('/var/www/index.html')) ? true : false; 
    $userRole = explode(",",$sandstormHeaders['X-Sandstorm-Permissions'])[0]; 
    $insideSanstorm = true;  
} else {
    $userRole = 'admin';
}

$do = (isset($_GET['do'])) ? $_GET['do'] : "" ;
$currentPage = 'home';

if (($do == 'create') && isset($_GET['page']) && ($userRole == 'admin')) {
        $currentPage = preg_replace('/[^A-Za-z0-9-]+/', '-', $_GET['page']);
        if (!is_dir('repo/pages/' . $currentPage) && !empty($currentPage)) { 
            mkdir('repo/pages/' . $currentPage) ;
            file_put_contents(content($currentPage), "", FILE_APPEND | LOCK_EX) ;
            file_put_contents(storage($currentPage), "", FILE_APPEND | LOCK_EX) ;
            $do = 'edit';
    } 
} elseif (isset($_GET['page']) && file_exists('repo/pages/' . $_GET['page'])) $currentPage = $_GET['page'];

$configStorageFile = 'repo/config.yaml';
$configContentFile = 'repo/config.html';
$config = (file_exists($configStorageFile)) ? spyc_load_file($configStorageFile) : array() ;

if ($userRole == 'admin' ) {
    switch ($do) {
    case "edit":
        $attributes = 'mv-format="text" mv-storage="' . content($currentPage) . '"';
        $html = '<h1>Edit <i>' . $currentPage . '</i></h1>' . "\n";
        $html .= '<textarea property="code" rows="30" cols="80"></textarea>';
        break;
    case "config":
        $attributes = ' mv-plugins="yaml" mv-format="yaml" mv-storage="' . $configStorageFile . '"';
        $html = file_get_contents($configContentFile);
        if ($insideSanstorm) $html .= publishingInfos();
        break;
    case "delete":
        if (isset($_GET['delConfirm'])) {
            unlink(content($currentPage));
            unlink(storage($currentPage));
            rmdir('repo/pages/' . $currentPage);
        }
        $attributes = 'mv-storage="' . storage('index') . '"';
        $html = file_get_contents(content('home'));
        break;
    case "publish":
        publishStaticImages();
        publishStaticPage('home');
        foreach (pagesList() as $item) {
            publishStaticPage($item);
        }
        $isPublic = 'true';
        $html = "<h1>Static web site</h1>\n";
        $html .= publishingInfos();
        break;
    case "unpublish":
        array_map('unlink', glob('/var/www/*'));
        $isPublic = 'false';
        $html = "<h1>Public site removed</h1>";
        break;
    default:
        $attributes = parameters() . ' mv-storage="' . storage($currentPage) . '"';
        $html = file_get_contents(content($currentPage));
    }
} else { 
    if ($userRole == "read") {
        $attributes =  ' mv-source="' . storage($currentPage) . '" mv-bar="none" ';
    } else {
        $attributes = parameters() . ' mv-storage="' . storage($currentPage) . '"';
    }
    $html = file_get_contents(content($currentPage));
}

/* ######################################################################
 * the current page rendering 
 * ######################################################################
*/

echo renderHeader(true);
echo renderAdminMenuToggle();
echo "<header>" . renderMenu() . "</header>\n";
echo '<main mv-app="grain" mv-storage-type="php" ' . $attributes . '>' .  $html . "</main>\n";
echo renderAdminMenu(); 
echo renderFooter(); 

/* ######################################################################
 * functions 
 * ######################################################################
*/

function content($page) {
    return 'repo/pages/' . $page . '/body.html';
}

function storage($page) {
    return 'repo/pages/' . $page . '/data.json';
}

function parameters() {
    global $config;
    $result = '';
    if ($config['autosave']) $result .= ' mv-autosave '; 
    if ($config['autoedit']) $result .= ' class="mv-autoedit" ';
   return $result; 
}

function renderHeader($static=false) {
    global $config;
    $result = '<html lang="en">'.
        '<head>'."\n".
        '<meta charset="UTF-8">'."\n".
        '<title>' . $config['title'] . '</title>'."\n".
        '<script src="https://get.mavo.io/mavo.js"></script>'."\n".
        '<script src="include/js/mavo-php.js"></script>'."\n".
        '<script src="include/js/mavo-yaml.js"></script>'."\n".
        '<link rel="stylesheet" href="https://get.mavo.io/mavo.css">'."\n".
        '<link rel="stylesheet" href="https://cdn.concisecss.com/concise.min.css">'."\n".
        '<link rel="stylesheet" href="https://cdn.concisecss.com/concise-utils/concise-utils.min.css">'."\n".
        '<link rel="stylesheet" href="https://cdn.concisecss.com/concise-ui/concise-ui.min.css">'."\n".
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">'."\n".
        '<link rel="stylesheet" href="include/css/menu.css">'."\n".
        '<link rel="stylesheet" href="include/css/style.css">'."\n".
        '</head>'."\n".
        '<body ' . (!empty($config['vocabulary']) ? 'vocab="'.$config['vocabulary'].'"' : "") . '>'."\n";
    return $result;
}

function renderAdminMenuToggle() {
    global $userRole, $config;
    if ($userRole == "admin") {
        $result = '<input type="checkbox" id="menu-toggle" />';
        $result .= '<label for="menu-toggle" class="menu-icon"><i class="fa fa-gear"></i></label>';
    }
    $result .= '<div class="content-container" container>';
    return $result;
}

function renderAdminMenu() {
    global $userRole, $config, $currentPage, $do, $insideSanstorm, $isPublic;
    $result = '</div>';
    if ($userRole == "admin") {
        $result .= '<div class="slideout-sidebar mv-ui"><ul>';
        $result .= '<li><h2>PAGE</h2></li>';
        if (!$do == "edit") $result .= editPageButton();
        $result .= addPageButton();
        if (count(pagesList()) > 0) $result .= delPageButton() ; 
        $result .= '<li><h2>MICRO-APP</h2></li>';
        if ($insideSanstorm && $config['allowPublicSite']) {
            $result .= ($isPublic == 'true') ? unPublishPublicSiteButton() : publishPublicSiteButton();
        }
        $result .= configAppButton();
        $result .= '</ul></div>';
        return $result;
    }
}

function renderMenu($static=false) {
    global $currentPage, $config, $do;
    $menu_app = ($config['title']) ? '<h1 class="_m0 _ts3 _ptxs _regular">' . $config['title'] . "</h1>\n" : "";
    if ((count(pagesList()) > 0) || ($do == 'config'))  {
        $urlPageKey = (!$static) ? '?page=' : "";
        $extensionPage = ($static) ? '.html' : "";
        $menu_app .= '<ul class="breadcrumb-nav">';
        $active = ($currentPage == 'home') ? ' -active' : '';
        $menu_app .= '<li class="item' . $active . '">';
        $menu_app .= '<a href="' . $urlPageKey . 'index' . $extensionPage . '"><i class="fa fa-home"></i></a>';
        $menu_app .= "</li>\n";
        foreach (pagesList() as $item) {
            $active = ($currentPage == $item) ? ' -active' : ''; 
            $menu_app .= '<li class="item' . $active . '">'.'
                <a href="' . $urlPageKey . $item . $extensionPage . '">    ' . $item . '</a></li>';
        }
        $menu_app .= "</ul>\n";
    } 
    return $menu_app ;
}

function editPageButton() {
    global $currentPage;
    $result = '<li><form method="GET">'.
        '<input type="submit" value="Edit current page">'.
        '<input type="hidden" name="do" value="edit">'.
        '<input type="hidden" name="page" value="' . $currentPage . '">'.
        '</form></li>';
    return $result;
}

function addPageButton() {
    $result = '<li><form method="GET">'.
        '<input type="submit" value="Add new page">'.
        '<input type="hidden" name="do" value="create">'.
        '<input type="text" name="page" required>'.
        '</form></li>';
    return $result;
}


function delPageButton() {
    $result = '<li><form method="GET">'.
        '<input type="submit" value="Delete this page:">'.
        '<input type="hidden" name="do" value="delete">'.
        '<select name="page"><option value=""></option>';
    foreach (pagesList() as $item) {
        $result .= '<option value="' . $item . '">    ' . $item . '</option>';
    }
    $result .= '</select><br />'.
        '<input type="checkbox" name="delConfirm" value="true" class="check" required>'. 
        '<small> I confirm this action</small>'.
        '</form></li>';
    return $result;
}

function unPublishPublicSiteButton() {
    $result = '<li><form method="GET">'.
        '<input type="submit" value="Unpublish">'.
        '<input type="hidden" name="do" value="unpublish">'.
        '</form></li>';
    return $result;
}

function publishPublicSiteButton() {
    $result = '<li><form method="GET">'.
        '<input type="submit" value="publish">'.
        '<input type="hidden" name="do" value="publish">'.
        '</form></li>';
    return $result;
}

function publishStaticPage($page) {
    $content = file_get_contents(content($page));
    $data = file_get_contents(storage($page));
    $static = renderHeader().
        '<header container>' . renderMenu('static') . '</header>' . "\n".
        '<main mv-app="static" mv-storage="'. $page . '.json" mv-bar="none" container>' .  $content . "</main>\n";
    $static .= renderFooter();
    file_put_contents('/var/www/' . $page . '.json', $data, LOCK_EX);
    file_put_contents('/var/www/' . (($page == "home") ? "index" : $page) . '.html', $static, LOCK_EX);
}

function publishStaticImages() {
    foreach (scandir("./repo/images") as $item) {
         if  (!($item == "." || $item == "..")) copy('/var/mavo/repo/images/' . $item, '/var/www/images/' . $item);
     }
}

function configAppButton() {
    $result = '<li><form method="GET">'.
        '<input type="submit" value="Settings">'.
        '<input type="hidden" name="do" value="config">'.
        '</form></li>';
    return $result;
}

function pagesList() {
    $result = array();
    foreach (scandir("repo/pages") as $dir) {
        $item = pathinfo($dir)['filename'];
        if  (!(empty($item) || $item == "." || $item == "home")) array_push($result, $item);
    }
    return $result;
}

function renderFooter() {
    global $config;
    $result = '<footer container class="_text-right">' .
        'A <a href="https://sandstorm.io/" target="_blank">Sandstorm</a> / ' .
        '<a href="https://mavo.io/" target="_blank">Mavo</a> micro-app' .
        ' — <a mailto:"' . $config['mail'] . '">' . $config['creator'] . '</a>' . 
        '</footer>';
    $result .= '</body>';
    $result .= '</html>';
    return $result;
}

function publishingInfos() {
    global $publishingInfo;
    $publicId = $publishingInfo[0];
    $autoUrl = $publishingInfo[2];
    $justHostOfAutoUrl = parse_url($autoUrl, PHP_URL_HOST);
    $result = '<p><b>How to preview the website for this micro-app?</b></p>'.
        '<p>You (and everyone else) can visit a static review of this micro-app at: <tt>'.
        '<a target="_blank" href="' . $autoUrl . '">' . $autoUrl . '</a></tt></p>'.
        '<p>If you like that site, you can make <tt>example.com</tt> show that if you do two things.</p>'.
        '<dl><dt>1. Add a <tt>CNAME</tt> record.</dt>'.
        '<dd><tt>example.com. IN CNAME ' . $justHostOfAutoUrl . '</tt></dd>'.
        '<dt>2. Add a <tt>TXT</tt> record.</dt>'.
        '<dd><tt>sandstorm-www.example.com. IN TXT ' . $publicId . '</tt></dd>'.
        '</dl>'.
        '<p>If you want, you can even put a CDN like CloudFlare in front of <tt>example.com</tt>! '.
        'Sandstorm will look at the <tt>TXT</tt> record to map the domain to the grain.</p>';
    return $result;
}

?>
