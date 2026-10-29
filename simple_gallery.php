<?php
/**
 * Plugin Name: Simple Gallery
 * Description: A lightweight multiple-folder image gallery plugin for GetSimple CMS CE.
 * Version: 1.0
 * Author: Zeth
 */

# Get the unique plugin id based on filename
$thisfile = basename(__FILE__, ".php");

# Register plugin with GetSimple
register_plugin(
    $thisfile,
    'Simple Gallery',
    '1.0',
    'Zeth',
    'https://getsimple-ce.ovh/',
    'A lightweight multiple-folder image gallery plugin for GetSimple CE.',
    'files',
    'simple_gallery_admin'
);

# Register backend sidebar menu link
add_action('files-sidebar', 'createSideMenu', array($thisfile, 'Simple Gallery'));

# Register filters for shortcodes in GetSimple frontend pages
add_filter('content', 'simple_gallery_shortcode_filter');
add_filter('exec_php_code', 'simple_gallery_shortcode_filter');

/**
 * Helper: Retrieve Gallery Settings
 */
function simple_gallery_get_settings() {
    $file = GSDATAOTHERPATH . 'simple_gallery_settings.json';
    $defaults = array(
        'grid_size'     => '180',
        'image_gap'     => '10',
        'border_radius' => '4',
        'grid_style'    => 'square' // Default layout: 'square', 'masonry', 'justified', 'columns', or 'metro'
    );

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            foreach ($defaults as $key => $default_val) {
                if (!isset($data[$key]) OR trim($data[$key]) === '') {
                    $data[$key] = $default_val;
                }
            }
            return array_merge($defaults, $data);
        }
    }

    return $defaults;
}

/**
 * Backend Administration Page
 */
function simple_gallery_admin() {
    $settings_file = GSDATAOTHERPATH . 'simple_gallery_settings.json';
    $message = '';

    // Handle Form Submission
    if (isset($_POST['submit_simple_gallery_settings'])) {
        $allowed_styles = array('square', 'masonry', 'justified', 'columns', 'metro');
        $selected_style = isset($_POST['grid_style']) && in_array($_POST['grid_style'], $allowed_styles) ? $_POST['grid_style'] : 'square';

        $settings = array(
            'grid_size'     => (isset($_POST['grid_size']) AND trim($_POST['grid_size']) !== '') ? (int)$_POST['grid_size'] : 180,
            'image_gap'     => (isset($_POST['image_gap']) AND trim($_POST['image_gap']) !== '') ? (int)$_POST['image_gap'] : 10,
            'border_radius' => (isset($_POST['border_radius']) AND trim($_POST['border_radius']) !== '') ? (int)$_POST['border_radius'] : 4,
            'grid_style'    => $selected_style
        );

        if (file_put_contents($settings_file, json_encode($settings))) {
            $message = '<div class="updated" style="margin-bottom:15px; padding:10px; background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:4px;">Settings saved successfully. Empty fields reverted to defaults.</div>';
        } else {
            $message = '<div class="error" style="margin-bottom:15px; padding:10px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:4px;">Error saving settings.</div>';
        }
    }

    // Load Settings
    $settings = simple_gallery_get_settings();

    echo '<h3>Simple Gallery Management</h3>';
    echo $message;
    
    ?>
    <style>
        .gs-admin-info-box {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-left: 4px solid #001529;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            word-break: break-word;
        }
        .gs-admin-info-box p {
            margin: 0 0 10px 0;
        }
        .gs-admin-info-box p:last-child {
            margin-bottom: 0;
        }
        .gs-admin-info-box code {
            background: #eef2f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            display: inline-block;
            max-width: 100%;
            overflow-x: auto;
        }
        .gs-admin-settings-row {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .gs-admin-field-group {
            display: flex;
            flex-direction: column;
        }
        .gs-admin-field-group input[type="number"] {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 120px;
        }
        @media (max-width: 768px) {
            .gs-admin-settings-row {
                flex-direction: column;
                gap: 15px;
            }
            .gs-admin-field-group {
                width: 100%;
            }
            .gs-admin-field-group input[type="number"] {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>

    <div class="gs-admin-info-box">
        <p>Create image galleries from multiple folders within your GetSimple uploads directory (e.g., <em><b>data/uploads/gallery/, data/uploads/portfolio/</b></em>).</p>
        <p><strong>Template Usage:</strong><br><code>&lt;?php if(function_exists('render_simple_gallery')) render_simple_gallery('folder_name'); ?&gt;</code></p>
        <p><strong>Shortcode Usage (inside page content):</strong><br><code>(% simple_gallery 'folder_name' %)</code></p>
    </div>

    <br>
    <form method="post" action="">
        <p><strong>Gallery Frontend Settings:</strong></p>
        <div class="gs-admin-settings-row">
            <div class="gs-admin-field-group">
                <label for="grid_size" style="font-weight: bold; margin-bottom: 5px;">Grid size / Min Width</label>
                <input type="number" id="grid_size" name="grid_size" value="<?php echo htmlspecialchars($settings['grid_size']); ?>">
            </div>
            <div class="gs-admin-field-group">
                <label for="image_gap" style="font-weight: bold; margin-bottom: 5px;">Image Gap</label>
                <input type="number" id="image_gap" name="image_gap" value="<?php echo htmlspecialchars($settings['image_gap']); ?>">
            </div>
            <div class="gs-admin-field-group">
                <label for="border_radius" style="font-weight: bold; margin-bottom: 5px;">Border radius</label>
                <input type="number" id="border_radius" name="border_radius" value="<?php echo htmlspecialchars($settings['border_radius']); ?>">
            </div>
        </div>

        <p><strong>Gallery Layout:</strong></p>
        <div style="margin-bottom: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
            <label style="cursor: pointer;">
                <input type="radio" name="grid_style" value="square" <?php echo ($settings['grid_style'] === 'square') ? 'checked' : ''; ?>> Square Grid
            </label>
            <label style="cursor: pointer;">
                <input type="radio" name="grid_style" value="masonry" <?php echo ($settings['grid_style'] === 'masonry') ? 'checked' : ''; ?>> Masonry Grid
            </label>
            <label style="cursor: pointer;">
                <input type="radio" name="grid_style" value="justified" <?php echo ($settings['grid_style'] === 'justified') ? 'checked' : ''; ?>> Justified Grid
            </label>
            <label style="cursor: pointer;">
                <input type="radio" name="grid_style" value="columns" <?php echo ($settings['grid_style'] === 'columns') ? 'checked' : ''; ?>> Column Grid
            </label>
            <label style="cursor: pointer;">
                <input type="radio" name="grid_style" value="metro" <?php echo ($settings['grid_style'] === 'metro') ? 'checked' : ''; ?>> Metro / Windows Grid
            </label>
        </div>

        <p>
            <input type="submit" name="submit_simple_gallery_settings" class="submit" value="Save" style="background: #001529; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; font-weight: bold;">
        </p>
    </form>
    <?php
}

/**
 * Filter to parse shortcodes in GetSimple page content: (% simple_gallery galleryname %)
 */
function simple_gallery_shortcode_filter($content) {
    $pattern = '/\(\%\s*simple_gallery\s+[\'"]?([a-zA-Z0-9_-]+)[\'"]?\s*\%\)/i';

    return preg_replace_callback($pattern, function($matches) {
        $gallery_folder = $matches[1];
        ob_start();
        render_simple_gallery($gallery_folder);
        return ob_get_clean();
    }, $content);
}

/**
 * Frontend Gallery Rendering Function
 * 
 * @param string $gallery_name The name of the subfolder inside data/uploads/
 */
function render_simple_gallery($gallery_name = 'gallery') {
    $gallery_name = preg_replace('/[^a-zA-Z0-9_-]/', '', $gallery_name);
    $dir = GSROOTPATH . 'data/uploads/' . $gallery_name . '/';
    $url = $GLOBALS['SITEURL'] . 'data/uploads/' . $gallery_name . '/';

    if (!is_dir($dir)) {
        echo '<p style="color:red;">Gallery folder <code>uploads/' . htmlspecialchars($gallery_name) . '/</code> not found.</p>';
        return;
    }

    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $images = array();

    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." AND $entry != "..") {
                $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_extensions)) {
                    $images[] = $entry;
                }
            }
        }
        closedir($handle);
    }

    if (empty($images)) {
        echo '<p>No images found in the gallery folder <code>uploads/' . htmlspecialchars($gallery_name) . '/</code>.</p>';
        return;
    }
    
    $settings = simple_gallery_get_settings();
    $grid_size     = (int)$settings['grid_size'];
    $image_gap     = (int)$settings['image_gap'];
    $border_radius = (int)$settings['border_radius'];
    $grid_style    = $settings['grid_style'];

    $gid = 'gs-gallery-' . preg_replace('/[^a-zA-Z0-9]/', '', $gallery_name);
    ?>
    <style>
    /* Square Layout CSS */
    .gs-gallery-grid.gs-style-square {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(<?php echo $grid_size; ?>px, 1fr));
        gap: <?php echo $image_gap; ?>px;
        margin: 20px 0;
    }
    .gs-style-square .gs-gallery-item {
        position: relative;
        width: 100%;
        padding-bottom: 100%;
        overflow: hidden;
        border-radius: <?php echo $border_radius; ?>px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .gs-style-square .gs-gallery-item img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.25s ease;
        cursor: pointer;
    }

    /* Masonry Layout CSS */
    .gs-gallery-grid.gs-style-masonry {
        column-count: auto;
        column-width: <?php echo $grid_size; ?>px;
        column-gap: <?php echo $image_gap; ?>px;
        margin: 20px 0;
    }
    .gs-style-masonry .gs-gallery-item {
        break-inside: avoid;
        margin-bottom: <?php echo $image_gap; ?>px;
        overflow: hidden;
        border-radius: <?php echo $border_radius; ?>px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .gs-style-masonry .gs-gallery-item img {
        display: block;
        width: 100%;
        height: auto;
        transition: transform 0.25s ease;
        cursor: pointer;
    }

    /* Justified Layout CSS (Flush Straight Bottoms) */
    .gs-gallery-grid.gs-style-justified {
        display: flex;
        flex-wrap: wrap;
        gap: <?php echo $image_gap; ?>px;
        margin: 20px 0;
    }
    .gs-style-justified .gs-gallery-item {
        position: relative;
        height: <?php echo $grid_size; ?>px;
        flex-grow: 1;
        overflow: hidden;
        border-radius: <?php echo $border_radius; ?>px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .gs-style-justified .gs-gallery-item img {
        height: 100%;
        width: 100%;
        object-fit: cover;
        vertical-align: bottom;
        transition: transform 0.25s ease;
        cursor: pointer;
    }

    /* Perfect Column Partitioning CSS (Uncropped & Flush Bottom) */
    .gs-gallery-grid.gs-style-columns {
        display: flex;
        flex-wrap: wrap;
        gap: <?php echo $image_gap; ?>px;
        margin: 20px 0;
    }
    .gs-style-columns .gs-column {
        flex: 1 1 <?php echo $grid_size; ?>px;
        display: flex;
        flex-direction: column;
        gap: <?php echo $image_gap; ?>px;
    }
    .gs-style-columns .gs-gallery-item {
        position: relative;
        width: 100%;
        overflow: hidden;
        border-radius: <?php echo $border_radius; ?>px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .gs-style-columns .gs-gallery-item img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.25s ease;
        cursor: pointer;
    }

    /* Metro / Windows-Style Grid CSS */
    .gs-gallery-grid.gs-style-metro {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(<?php echo $grid_size; ?>px, 1fr));
        grid-auto-rows: <?php echo $grid_size; ?>px;
        grid-auto-flow: dense;
        gap: <?php echo $image_gap; ?>px;
        margin: 20px 0;
    }
    .gs-style-metro .gs-gallery-item {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
        border-radius: <?php echo $border_radius; ?>px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    /* Dynamic Metro Tile Sizes */
    .gs-style-metro .gs-gallery-item.metro-tile-2x2 {
        grid-column: span 2;
        grid-row: span 2;
    }
    .gs-style-metro .gs-gallery-item.metro-tile-2x1 {
        grid-column: span 2;
        grid-row: span 1;
    }
    .gs-style-metro .gs-gallery-item.metro-tile-1x2 {
        grid-column: span 1;
        grid-row: span 2;
    }
    .gs-style-metro .gs-gallery-item.metro-tile-1x1 {
        grid-column: span 1;
        grid-row: span 1;
    }
    .gs-style-metro .gs-gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.25s ease;
        cursor: pointer;
    }
    @media (max-width: 600px) {
        .gs-style-metro .gs-gallery-item.metro-tile-2x2,
        .gs-style-metro .gs-gallery-item.metro-tile-2x1 {
            grid-column: span 1;
        }
    }

    .gs-gallery-item img:hover {
        transform: scale(1.08);
    }
    </style>

    <?php if ($grid_style === 'columns'): ?>
        <?php
        // Process images & calculate heights for flush bottom partitioning
        $img_data = array();
        foreach ($images as $img) {
            $file_path = $dir . $img;
            $aspect_ratio = 1;
            if (file_exists($file_path)) {
                $size = @getimagesize($file_path);
                if ($size && $size[0] > 0 && $size[1] > 0) {
                    $aspect_ratio = $size[0] / $size[1]; // width / height
                }
            }
            $img_data[] = array(
                'filename' => $img,
                'ratio'    => $aspect_ratio
            );
        }

        // Determine number of columns (3 as optimal baseline or target column calculation)
        $num_columns = 3;
        $cols = array_fill(0, $num_columns, array());
        $col_sum_inv_ratios = array_fill(0, $num_columns, 0);

        // Greedy distribution: put image into column with smallest relative height
        foreach ($img_data as $data) {
            $min_col = 0;
            $min_val = $col_sum_inv_ratios[0];
            for ($c = 1; $c < $num_columns; $c++) {
                if ($col_sum_inv_ratios[$c] < $min_val) {
                    $min_val = $col_sum_inv_ratios[$c];
                    $min_col = $c;
                }
            }
            $cols[$min_col][] = $data;
            $col_sum_inv_ratios[$min_col] += (1 / $data['ratio']);
        }

        // Calculate max normalized height across columns
        $max_col_height = max($col_sum_inv_ratios);
        ?>

        <div class="gs-gallery-grid gs-style-columns <?php echo $gid; ?>">
            <?php for ($c = 0; $c < $num_columns; $c++): ?>
                <?php if (empty($cols[$c])) continue; ?>
                <div class="gs-column">
                    <?php 
                    $count = count($cols[$c]);
                    $current_sum = $col_sum_inv_ratios[$c];
                    foreach ($cols[$c] as $idx => $data):
                        $inv_r = 1 / $data['ratio'];
                        // Scale height proportionally so column bottom aligns perfectly
                        $flex_height = ($max_col_height > 0 && $current_sum > 0) 
                            ? ($inv_r / $current_sum) * 100 
                            : (100 / $count);
                    ?>
                        <div class="gs-gallery-item" style="flex: <?php echo sprintf('%.4f', $flex_height); ?> 1 0%;">
                            <img src="<?php echo $url . $data['filename']; ?>" alt="<?php echo htmlspecialchars($data['filename']); ?>" data-full="<?php echo $url . $data['filename']; ?>" class="gs-lightbox-trigger">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>
        </div>

    <?php elseif ($grid_style === 'metro'): ?>

        <div class="gs-gallery-grid gs-style-metro <?php echo $gid; ?>">
        <?php 
        $metro_patterns = array('metro-tile-2x2', 'metro-tile-1x1', 'metro-tile-1x1', 'metro-tile-2x1', 'metro-tile-1x1', 'metro-tile-1x2', 'metro-tile-1x1');
        $tile_count = count($metro_patterns);
        foreach ($images as $index => $img): 
            $tile_class = $metro_patterns[$index % $tile_count];
        ?>
            <div class="gs-gallery-item <?php echo $tile_class; ?>">
                <img src="<?php echo $url . $img; ?>" alt="<?php echo htmlspecialchars($img); ?>" data-full="<?php echo $url . $img; ?>" class="gs-lightbox-trigger">
            </div>
        <?php endforeach; ?>
        </div>

    <?php else: ?>

        <div class="gs-gallery-grid gs-style-<?php echo $grid_style; ?> <?php echo $gid; ?>">
        <?php foreach ($images as $img): ?>
            <div class="gs-gallery-item">
                <img src="<?php echo $url . $img; ?>" alt="<?php echo htmlspecialchars($img); ?>" data-full="<?php echo $url . $img; ?>" class="gs-lightbox-trigger">
            </div>
        <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- Global Shared Modal Container -->
    <?php static $modal_injected = false;
    if (!$modal_injected):
        $modal_injected = true;
    ?>
    <style>
    #gs-lightbox-modal {
        visibility: hidden;
        opacity: 0;
        position: fixed;
        z-index: 99999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85);
        display: flex;
        justify-content: center;
        align-items: center;
        box-sizing: border-box;
        overflow: hidden;
        transition: opacity 0.35s ease, visibility 0.35s ease;
    }
    #gs-lightbox-modal.active {
        visibility: visible;
        opacity: 1;
    }
    #gs-lightbox-modal img {
        position: absolute;
        max-width: 90vw;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 6px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.6);
        will-change: transform, width, height, top, left, opacity;
    }
    .gs-lightbox-controls {
        position: absolute;
        top: 20px;
        right: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 100000;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    #gs-lightbox-modal.active .gs-lightbox-controls {
        opacity: 1;
    }
    .gs-lightbox-btn {
        background: none !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        color: #fff;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.9;
        transition: opacity 0.2s ease;
    }
    .gs-lightbox-btn:focus,
    .gs-lightbox-btn:active {
        outline: none !important;
        box-shadow: none !important;
        border: none !important;
    }
    .gs-lightbox-btn:hover {
        opacity: 0.6;
    }
    #gs-lightbox-close {
        font-size: 40px;
        font-weight: bold;
        line-height: 1;
    }
    #gs-lightbox-fs svg {
        width: 26px;
        height: 26px;
        fill: currentColor;
    }
    
    /* Navigation Arrows */
    .gs-lightbox-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: none !important;
        color: #fff;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        font-size: 28px;
        width: 50px;
        height: 50px;
        cursor: pointer;
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .gs-lightbox-nav:focus,
    .gs-lightbox-nav:active {
        outline: none !important;
        box-shadow: none !important;
        border: none !important;
    }
    #gs-lightbox-modal.active .gs-lightbox-nav {
        opacity: 1;
    }
    .gs-lightbox-nav:hover {
        opacity: 0.6 !important;
    }
    #gs-lightbox-prev {
        left: 25px;

    }
    #gs-lightbox-next {
        right: 25px;
    }

    </style>

    <div id="gs-lightbox-modal">
        <div class="gs-lightbox-controls">
            <button id="gs-lightbox-fs" class="gs-lightbox-btn" title="Toggle Fullscreen">
                <svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
            </button>
            <button id="gs-lightbox-close" class="gs-lightbox-btn" title="Close">&times;</button>
        </div>
        <button id="gs-lightbox-prev" class="gs-lightbox-nav" title="Previous Image">&#10094;</button>
        <button id="gs-lightbox-next" class="gs-lightbox-nav" title="Next Image">&#10095;</button>
        <img id="gs-lightbox-img" src="" alt="Zoomed Image">
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("gs-lightbox-modal");
        const modalImg = document.getElementById("gs-lightbox-img");
        const closeBtn = document.getElementById("gs-lightbox-close");
        const fsBtn = document.getElementById("gs-lightbox-fs");
        const prevBtn = document.getElementById("gs-lightbox-prev");
        const nextBtn = document.getElementById("gs-lightbox-next");

        let activeThumb = null;
        let galleryImages = [];
        let currentIndex = -1;
        let baseTargetLeft = 0;
        let baseTargetTop = 0;
        let baseTargetWidth = 0;
        let baseTargetHeight = 0;

        document.body.addEventListener("click", function (e) {
            if (e.target.classList.contains("gs-lightbox-trigger")) {
                activeThumb = e.target;
                
                const grid = activeThumb.closest(".gs-gallery-grid");
                if (grid) {
                    galleryImages = Array.from(grid.querySelectorAll(".gs-lightbox-trigger"));
                    currentIndex = galleryImages.indexOf(activeThumb);
                } else {
                    galleryImages = [activeThumb];
                    currentIndex = 0;
                }

                const thumbRect = activeThumb.getBoundingClientRect();
                const fullSrc = activeThumb.getAttribute("data-full");

                modalImg.style.transition = "none";
                modalImg.style.opacity = "1";
                modalImg.style.transform = "none";
                modalImg.style.top = thumbRect.top + "px";
                modalImg.style.left = thumbRect.left + "px";
                modalImg.style.width = thumbRect.width + "px";
                modalImg.style.height = thumbRect.height + "px";
                
                modalImg.src = fullSrc;
                modal.classList.add("active");

                void modalImg.offsetWidth;

                modalImg.style.transition = "all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1)";
                updateModalImageSize();
            }
        });

        function updateModalImageSize() {
            const maxWidth = window.innerWidth * 0.9;
            const maxHeight = window.innerHeight * 0.9;
            
            let targetWidth = modalImg.naturalWidth || maxWidth;
            let targetHeight = modalImg.naturalHeight || maxHeight;

            const ratio = Math.min(maxWidth / targetWidth, maxHeight / targetHeight);
            targetWidth = targetWidth * ratio;
            targetHeight = targetHeight * ratio;

            baseTargetTop = (window.innerHeight - targetHeight) / 2;
            baseTargetLeft = (window.innerWidth - targetWidth) / 2;
            baseTargetWidth = targetWidth;
            baseTargetHeight = targetHeight;

            modalImg.style.top = baseTargetTop + "px";
            modalImg.style.left = baseTargetLeft + "px";
            modalImg.style.width = baseTargetWidth + "px";
            modalImg.style.height = baseTargetHeight + "px";
            modalImg.style.transform = "none";
        }

        window.addEventListener("resize", function() {
            if (modal.classList.contains("active")) {
                updateModalImageSize();
            }
        });

        function showImage(direction) {
            if (galleryImages.length === 0) return;
            currentIndex = (currentIndex + direction + galleryImages.length) % galleryImages.length;
            activeThumb = galleryImages[currentIndex];
            const fullSrc = activeThumb.getAttribute("data-full");

            const slideDistance = direction > 0 ? -window.innerWidth : window.innerWidth;
            
            modalImg.style.transition = "transform 0.25s ease-in, opacity 0.25s ease-in";
            modalImg.style.transform = `translateX(${slideDistance}px)`;
            modalImg.style.opacity = "0";

            setTimeout(() => {
                modalImg.src = fullSrc;
                
                const applyNewImage = () => {
                    updateModalImageSize();
                    const entryDistance = direction > 0 ? window.innerWidth : -window.innerWidth;
                    modalImg.style.transition = "none";
                    modalImg.style.transform = `translateX(${entryDistance}px)`;
                    modalImg.style.opacity = "0";

                    void modalImg.offsetWidth;

                    modalImg.style.transition = "transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.3s ease";
                    modalImg.style.transform = "translateX(0px)";
                    modalImg.style.opacity = "1";
                };

                if (modalImg.complete) {
                    applyNewImage();
                } else {
                    modalImg.onload = applyNewImage;
                }
            }, 250);
        }

        prevBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            showImage(-1);
        });

        nextBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            showImage(1);
        });

        let touchStartX = 0;
        let touchCurrentX = 0;
        let isSwiping = false;

        modal.addEventListener("touchstart", function(e) {
            touchStartX = e.touches[0].clientX;
            touchCurrentX = touchStartX;
            isSwiping = true;
            modalImg.style.transition = "none";
        }, {passive: true});

        modal.addEventListener("touchmove", function(e) {
            if (!isSwiping) return;
            touchCurrentX = e.touches[0].clientX;
            const diffX = touchCurrentX - touchStartX;
            modalImg.style.transform = `translateX(${diffX}px)`;
            modalImg.style.opacity = Math.max(0.3, 1 - Math.abs(diffX) / (window.innerWidth * 0.8));
        }, {passive: true});

        modal.addEventListener("touchend", function(e) {
            if (!isSwiping) return;
            isSwiping = false;
            const diffX = touchCurrentX - touchStartX;
            const threshold = 60;

            if (diffX < -threshold) {
                showImage(1);
            } else if (diffX > threshold) {
                showImage(-1);
            } else {
                modalImg.style.transition = "transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 0.25s ease";
                modalImg.style.transform = "translateX(0px)";
                modalImg.style.opacity = "1";
            }
        }, {passive: true});

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                if (modal.requestFullscreen) {
                    modal.requestFullscreen();
                } else if (modal.webkitRequestFullscreen) {
                    modal.webkitRequestFullscreen();
                } else if (modal.msRequestFullscreen) {
                    modal.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        fsBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            toggleFullscreen();
        });

        function closeModal() {
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(() => {});
            }

            if (!activeThumb) {
                modal.classList.remove("active");
                return;
            }

            const thumbRect = activeThumb.getBoundingClientRect();
            modalImg.style.transition = "all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1)";
            modalImg.style.transform = "none";
            modalImg.style.top = thumbRect.top + "px";
            modalImg.style.left = thumbRect.left + "px";
            modalImg.style.width = thumbRect.width + "px";
            modalImg.style.height = thumbRect.height + "px";

            modal.classList.remove("active");

            setTimeout(() => {
                modalImg.src = "";
                activeThumb = null;
            }, 350);
        }

        closeBtn.addEventListener("click", closeModal);
        modal.addEventListener("click", function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener("keydown", function(e) {
            if (!modal.classList.contains("active")) return;
            if (e.key === "Escape") {
                closeModal();
            } else if (e.key === "ArrowRight") {
                showImage(1);
            } else if (e.key === "ArrowLeft") {
                showImage(-1);
            }
        });
    });
    </script>
    <?php endif; ?>
<?php
}