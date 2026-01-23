<?php

/**
 * Arcane 26.01.2 Microframework
 * Copyright 2017-2026 Joshua Britt
 * MIT License https://arcane.dev
**/

$define['DIR'] = [
  'HELPERS' => '/helpers/',
  'IMAGES' => '/images/',
  'LAYOUTS' => '/layouts/',
  'LOCALES' => '/locales/',
  'PAGES' => '/pages/',
  'SCRIPTS' => '/scripts/',
  'STYLES' => '/styles/'
];

$define['SET'] = [
  'ERRORS' => false,
  'INDEX' => 'index',
  'LAYOUT' => null,
  'LOCALE' => null,
  'MINIFY' => true
];

function env($variable, $default = null) {
  $variable = getenv($variable) ?: $default;
  
  if(in_array($variable, ['true', 'false', 'null'], true)) {
    return json_decode($variable);
  }
  
  return $variable;
}

function path($locator = null, $actual = false) {
  if(is_null($locator)) {
    return str_replace('//', '/', '/' . implode('/', URI) . '/');
  } else if(is_int($locator)) {
    return URI[$locator] ?? null;
  } else {
    $prepend = $actual ? APP['DIR'] : APP['ROOT'];
    
    if(is_array($locator)) {
      list($define, $locator) = [$locator[0], $locator[1] ?? null];
      
      if(!is_null($define)) {
        $define = DIR[strtoupper($define)];
        
        if(isset($define) && !empty($define)) {
          $locator = "{$define}/{$locator}";
        }
      }
    }
    
    if(strpos($locator, '.') === false) {
      if(defined('LOCALE') && !$actual) {
        $prepend = LOCALE['URI'];
      }
      
      if(strpos($locator, '?') === false) {
        $locator = "{$locator}/";
      }
    }
    
    $locator = "{$prepend}/{$locator}";
    $locator = preg_replace("#(^|[^:])//+#", "\\1/", $locator);
    
    return $locator;
  }
}

function relay($define, $content) {
  if(is_callable($content)) {
    ob_start();
      $content();
    $content = ob_get_clean();
  }
  
  define(strtoupper($define), $content);
}

function scribe($string, $replace = []) {
  if(is_array($string)) {
    list($string, $return) = [$string[0], $string[1] ?? ''];
  }
  
  if(defined('TRANSCRIPT')) {
    if(array_key_exists($string, TRANSCRIPT)) {
      list($string, $return) = [TRANSCRIPT[$string], null];
    }
  }
  
  if(isset($return)) {
    $string = $return !== '' ? $return : null;
  } else if(!empty($replace)) {
    $string = strtr($string, $replace);
  }
  
  return $string;
}

(function() use($define) {
  $app = [
    'DIR' => dirname($_SERVER['SCRIPT_FILENAME']) . '/',
    'ROOT' => rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/',
    'QUERY' => urldecode($_SERVER['QUERY_STRING']),
    'URI' => strtok($_SERVER['REQUEST_URI'], '?')
  ];
  
  define('APP', array_merge($app, [
    'DIR' => str_replace('\\', '/', $app['DIR']),
    'ROOT' => substr($app['DIR'], strlen($app['ROOT']) - 1)
  ]));
  
  if(file_exists('.env') || file_exists('.env.example')) {
    $envs = file_exists('.env') ? file('.env') : file('.env.example');
    
    foreach(array_filter(array_map('trim', $envs)) as $env) {
      if(substr($env, 0, 1) != '#') {
        putenv($env);
      }
    }
    
    if(file_exists('.gitignore')) {
      $gitignore = array_filter(array_map('trim', file('.gitignore')));
      
      if(!in_array('.env', $gitignore) && !in_array('*', $gitignore)) {
        file_put_contents('.gitignore', "\n.env", FILE_APPEND);
      }
    }
  }
  
  if(!file_exists('.htaccess')) {
    $htaccess = implode("\n", [
      '<IfModule mod_rewrite.c>',
      '  RewriteEngine On',
      '  RewriteRule (^|/)\. - [F]',
      '  RewriteCond %{REQUEST_URI} !(/$|\.|^$)',
      '  RewriteRule ^(.*)$ %{REQUEST_URI}/ [L,R=301]',
      '  RewriteCond %{REQUEST_FILENAME} !-f',
      '  RewriteRule . index.php [L]',
      '  RewriteCond %{REQUEST_FILENAME} -d',
      '  RewriteRule . index.php [L]',
      '</IfModule>'
    ]);
    
    file_put_contents('.htaccess', $htaccess);
  }
  
  foreach($define as $constant => $array) {
    foreach($array as $key => $default) {
      $array[$key] = env("{$constant}_{$key}", $default);
    }
    
    define($constant, $array);
  }
  
  foreach(DIR as $type => $path) {
    $path = trim($path, '/') . '/';
    
    if(!is_dir($path) && !empty($path)) {
      mkdir($path, 0755, true);
      
      if($type === 'PAGES') {
        $html = implode("\n", [
          '<html>',
          '  <body>',
          '    <h1>Hello, world!</h1>',
          '  </body>',
          '</html>'
        ]);
        
        file_put_contents($path . SET['INDEX'] . '.php', $html);
      }
    }
  }
})();

(function() {
  $directory = rtrim(path(DIR['LOCALES'], true), '/');
  
  foreach(glob("{$directory}/*/*[-+]*.json") as $locale) {
    $tag = basename($locale, '.json');
    $major = basename(dirname($locale));
    $minor = explode('-', str_replace('+', '-', $tag), 2);
    $minor = $minor[0] === $major ? $minor[1] : $minor[0];
    
    if(ctype_alpha($minor)) {
      $files = [
        dirname($locale, 2) . "/{$minor}.json",
        dirname($locale) . "/{$major}.json",
        $locale
      ];
      
      switch(substr($tag, 3)) {
        case $major:
          list($language, $country) = [$minor, $major];
        break;
        
        case $minor:
          list($language, $country) = [$major, $minor];
        break;
      }
      
      if(strpos($locale, '+') !== false) {
        $uri = $major;
        $minor = null;
      } else {
        $uri = "{$major}/{$minor}";
      }
      
      $locales[$major][$minor] = [
        'CODE' => "{$language}-{$country}",
        'COUNTRY' => $country,
        'FILES' => $files,
        'LANGUAGE' => $language,
        'URI' => APP['ROOT'] . "{$uri}/",
      ];
    }
  }
  
  define('LOCALES', $locales ?? []);
})();

(function() {
  $uri = rtrim(substr(APP['URI'], strlen(APP['ROOT'])), '/');
  $uri = array_filter(array_merge([''], explode('/', $uri)), 'strlen');
  
  if(!empty($uri)) {
    if(array_key_exists($uri[1], LOCALES)) {
      if(isset($uri[2]) && array_key_exists($uri[2], LOCALES[$uri[1]])) {
        $locale = LOCALES[$uri[1]][$uri[2]];
        
        array_splice($uri, 0, 2);
      } else if(array_key_exists(null, LOCALES[$uri[1]])) {
        $locale = LOCALES[$uri[1]][null];
        
        array_splice($uri, 0, 1);
      }
    }
    
    if(isset($locale)) {
      define('LOCALE', $locale);
    }
    
    if(!empty($uri)) {
      $uri = array_filter(array_merge([''], $uri), 'strlen');
    }
  }

  define('URI', $uri);

  if(defined('LOCALE')) {
    foreach(LOCALE['FILES'] as $file) {
      if(file_exists($file)) {
        $file = json_decode(file_get_contents($file), true) ?? [];
        $transcript = $file + ($transcript ?? []);
      }
    }
    
    define('TRANSCRIPT', $transcript);
  } else if(!empty(SET['LOCALE'])) {
    $request = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $default = str_replace('+', '-', SET['LOCALE']);
    $uri = implode('/', URI);
    
    preg_match_all("/[a-z]{2}-[a-z]{2}/i", $request, $request);
    
    foreach(array_merge(reset($request), [$default]) as $code) {
      foreach(LOCALES as $locales) {
        foreach($locales as $locale) {
          if(!strcasecmp($locale['CODE'], $code)) {
            header("Location: {$locale['URI']}" . ltrim("{$uri}/", '/'));
            
            exit;
          }
        }
      }
    }
  }
})();

(function() {
  $path = URI;
  
  ini_set('display_errors', SET['ERRORS'] ? 1 : 0);
  
  if(SET['ERRORS']) {
    error_reporting(E_ALL);
  } else {
    error_reporting(E_ALL & ~(E_NOTICE|E_DEPRECATED));
  }
  
  do {
    $page = path(DIR['PAGES'], true) . implode('/', $path) . '.php';
    
    if(!is_file($page) && is_dir(substr($page, 0, -4) . '/')) {
      $page = rtrim(str_replace('.php', '', $page), '/');
      $page = "$page/" . SET['INDEX'] . '.php';
    }
    
    if(is_file($page) && end($path) !== SET['INDEX']) {
      define('PAGEFILE', $page);
      
      break;
    } else if(empty($path)) {
      exit;
    }
    
    array_pop($path);
  } while(true);
  
  define('PATH', $path);
})();

(function() {
  $directory = trim(str_replace([APP['DIR'], '.php'], '', PAGEFILE), '/');
  
  do {
    $paths[] = $directory;
    $directory = dirname($directory);
  } while($directory != '.');
  
  $paths = array_filter(array_merge([''], array_reverse($paths)), 'strlen');
  
  define('PATHS', $paths);
  
  foreach(PATHS as $directory) {
    $directory = trim(DIR['HELPERS'], '/') . strstr($directory, '/');
    $directory = rtrim(path($directory, true), '/');
    
    if(is_dir($directory)) {
      foreach(glob("{$directory}/*.php") as $helper) {
        $helpers[basename($helper, '.php')] = include($helper);
      }
    }
  }
  
  $GLOBALS['helpers'] = $helpers ?? [];
})();

(function() {
  $path = PATH;
  
  if(defined('PAGEFILE')) {
    relay('CONTENT', function() {
      extract($GLOBALS['helpers'], EXTR_SKIP);
      
      require PAGEFILE;
    });
  }
  
  if(defined('ROUTES')) {
    $facade = array_diff_assoc(URI, $path);
    
    foreach(ROUTES as $route) {
      if(!is_array($route)) {
        $route = explode('/', trim($route, '/'));
      }
      
      if(count($route) === count($facade)) {
        foreach(array_values($facade) as $increment => $segment) {
          if(is_array($route[$increment])) {
            if(!in_array($segment, $route[$increment])) {
              break;
            }
          } else if($route[$increment] !== $segment) {
            break;
          }
          
          if(end($facade) === $segment) {
            $path = $path + $facade;
            
            break 2;
          }
        }
      }
    }
  }
  
  if(array_diff(URI, $path)) {
    header('Location: ' . path(implode('/', $path)));
    
    exit;
  } else {
    if(defined('REDIRECT')) {
      if(!array_key_exists('host', parse_url(REDIRECT))) {
        $redirect = path(REDIRECT);
      }
      
      header('Location: ' . ($redirect ?? REDIRECT));
      
      exit;
    }
    
    if(defined('LAYOUT') || !empty(SET['LAYOUT'])) {
      $layout = defined('LAYOUT') ? LAYOUT : SET['LAYOUT'];
      $layout = path(DIR['LAYOUTS'] . "/{$layout}.php", true);
      
      if(file_exists($layout)) {
        define('LAYOUTFILE', $layout);
        
        foreach([
          'js' => 'SCRIPTS',
          'css' => 'STYLES'
        ] as $extension => $constant) {
          $assets = array_merge([
            trim(DIR['LAYOUTS'], '/') . ".{$extension}",
            (defined('LAYOUT') ? LAYOUT : SET['LAYOUT']) . ".{$extension}"
          ], preg_filter("/$/", ".{$extension}", PATHS));
          
          relay($constant, function() use($assets, $constant) {
            $html = [
              'SCRIPTS' => '<script src="%s"></script>',
              'STYLES' => '<link rel="stylesheet" href="%s" />'
            ];
            
            foreach($assets as $asset) {
              $asset = path([$constant, $asset], true);
              
              if(file_exists($asset)) {
                $asset = "/{$asset}?m=" . filemtime($asset);
                $asset = path(str_replace(APP['DIR'], '', $asset));
                
                echo sprintf($html[$constant], $asset);
              }
            }
          });
        }
      }
    }
  }
})();

(function() {
  ob_start(function($content) {
    if(SET['MINIFY']) {
      $minify = [
        "/\>\h+$/m" => ">",
        "/\>[^\S ]+/m" => ">",
        "/^\h+\</m" => "<",
        "/[^\S ]+\</m" => "<",
        "/\>\s{2,}\</" => "><",
        "/\<\!--.*?-->/" => ""
      ];
      
      return preg_replace(array_keys($minify), $minify, $content);
    } else {
      return $content;
    }
  });
    if(defined('LAYOUTFILE')) {
      extract($GLOBALS['helpers'], EXTR_SKIP);
      
      require LAYOUTFILE;
    } else {
      echo CONTENT;
    }
  ob_end_flush();
})();

?>