<?php
/**
 * Meta Tags Helper
 * Generate consistent meta tags across all pages
 */

class MetaTags {
    
    public static function generate($pageTitle = '', $description = '', $keywords = '', $image = '') {
        $siteName = getSetting('site_name', 'BTIKP Kalimantan Selatan');
        $siteDescription = getSetting('site_description', 'Portal resmi BTIKP Provinsi Kalimantan Selatan');
        $siteKeywords = getSetting('site_keywords', 'btikp, kalsel, pendidikan');
        $siteLogo = getSetting('site_logo');
        
        // Use defaults if not provided
        $description = $description ?: $siteDescription;
        $keywords = $keywords ?: $siteKeywords;
        $image = $image ?: ($siteLogo ? uploadUrl($siteLogo) : '');
        
        // Full title
        $fullTitle = $pageTitle ? "$pageTitle - $siteName" : $siteName;
        
        // Current URL
        $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                     . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        $tags = [
            // Basic Meta
            'title' => $fullTitle,
            'description' => $description,
            'keywords' => $keywords,
            
            // Open Graph
            'og:title' => $fullTitle,
            'og:description' => $description,
            'og:image' => $image,
            'og:url' => $currentUrl,
            'og:type' => 'website',
            'og:site_name' => $siteName,
            
            // Twitter Card
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $fullTitle,
            'twitter:description' => $description,
            'twitter:image' => $image,
        ];
        
        return $tags;
    }
    
    public static function render($pageTitle = '', $description = '', $keywords = '', $image = '') {
        $tags = self::generate($pageTitle, $description, $keywords, $image);
        
        $html = '<title>' . htmlspecialchars($tags['title']) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . htmlspecialchars($tags['description']) . '">' . "\n";
        $html .= '<meta name="keywords" content="' . htmlspecialchars($tags['keywords']) . '">' . "\n";
        
        // Open Graph
        foreach ($tags as $key => $value) {
            if (strpos($key, 'og:') === 0) {
                $html .= '<meta property="' . $key . '" content="' . htmlspecialchars($value) . '">' . "\n";
            }
            if (strpos($key, 'twitter:') === 0) {
                $html .= '<meta name="' . $key . '" content="' . htmlspecialchars($value) . '">' . "\n";
            }
        }
        
        return $html;
    }
}
