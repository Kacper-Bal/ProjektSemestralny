<?php
require_once('auth.php');
require_once('conn.php');

$stmtFeatured = $conn->prepare("SELECT g.*, 
        p.name as publisher_name, 
        d.name as developer_name,
        MAX(pr.discount_percent) as discount_percent,
        (SELECT COUNT(*) FROM user_games ug WHERE ug.game_id = g.id) as popularity
        FROM games g
        LEFT JOIN publishers p ON g.publisher_id = p.id
        LEFT JOIN developers d ON g.developer_id = d.id
        LEFT JOIN promotion_games pg ON g.id = pg.game_id
        LEFT JOIN promotions pr ON pg.promotion_id = pr.id 
            AND NOW() BETWEEN pr.start_date AND pr.end_date
        GROUP BY g.id
        ORDER BY popularity DESC, g.name ASC
        LIMIT 10");

$stmtFeatured->execute();
$result = $stmtFeatured->get_result();

$stmtDiscount = $conn->prepare("SELECT g.*, 
        p.name as publisher_name, 
        d.name as developer_name,
        MAX(pr.discount_percent) as discount_percent,
        (SELECT COUNT(*) FROM user_games ug WHERE ug.game_id = g.id) as popularity
        FROM games g
        JOIN promotion_games pg ON g.id = pg.game_id
        JOIN promotions pr ON pg.promotion_id = pr.id 
            AND NOW() BETWEEN pr.start_date AND pr.end_date
        LEFT JOIN publishers p ON g.publisher_id = p.id
        LEFT JOIN developers d ON g.developer_id = d.id
        GROUP BY g.id
        ORDER BY RAND()
        LIMIT 10");

$stmtDiscount->execute();
$resultDiscount = $stmtDiscount->get_result();

$stmtTags = $conn->prepare("SELECT * FROM tags");
        $stmtTags->execute();
        $resultTags = $stmtTags->get_result();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleIndex.css">
</head>
<body>
    <?php include('header.php'); ?>

    <div id="main-content">
        
        <div class="featured-container">
            <h2 class="section-title">Wyróżnione i Polecane</h2>
            
            <div class="carousel-wrapper">
                
                <button class="nav-btn prev-btn" id="btnPrev">&#10094;</button>

                <div class="featured-carousel" id="featuredCarousel">
                    
                    <?php 
                    $slideIndex = 0;
                    if ($result && $result->num_rows > 0):
                        while ($game = $result->fetch_assoc()): 
                            
                            $baseName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($game['name']));
                            $img1 = "img/games/{$baseName}_1.jpg";
                            $img2 = "img/games/{$baseName}_2.jpg";
                            $img3 = "img/games/{$baseName}_3.jpg";
                            $img4 = "img/games/{$baseName}_4.jpg";
                            
                            if(!file_exists($img1)) $img1 = "img/others/loginBackground.jpg"; 
                            
                            $price = $game['price'];
                            $discount = $game['discount_percent'];
                            
                            if ($price == 0) {
                                $priceDisplay = '<span class="current-price">Free to Play</span>';
                            } elseif ($discount) {
                                $discountedPrice = $price * (1 - $discount / 100);
                                $priceDisplay = '
                                    <div class="discount-block">
                                        <span class="discount-badge">-' . $discount . '%</span>
                                        <div class="price-stack">
                                            <span class="old-price">' . number_format($price, 2) . ' zł</span>
                                            <span class="current-price">' . number_format($discountedPrice, 2) . ' zł</span>
                                        </div>
                                    </div>';
                            } else {
                                $priceDisplay = '<span class="current-price">' . number_format($price, 2) . ' zł</span>';
                            }
                    ?>
                    
                    <a href="game.php?game=<?php echo urlencode($game['name']); ?>" class="featured-item <?php echo $slideIndex === 0 ? 'active' : ''; ?>" data-index="<?php echo $slideIndex; ?>">
                        
                        <div class="featured-left">
                            <img src="<?php echo $img1; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="main-featured-img">
                        </div>

                        <div class="featured-right">
                            <h3 class="featured-title"><?php echo htmlspecialchars($game['name']); ?></h3>
                            
                            <div class="featured-thumbs-grid">
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img1; ?>')"><img src="<?php echo $img1; ?>" alt=""></div>
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img2; ?>')"><img src="<?php echo $img2; ?>" alt=""></div>
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img3; ?>')"><img src="<?php echo $img3; ?>" alt=""></div>
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img4; ?>')"><img src="<?php echo $img4; ?>" alt=""></div>
                            </div>

                            <div class="featured-meta">
                                <p>Wydawca: <span><?php echo htmlspecialchars($game['publisher_name'] ?? 'N/A'); ?></span></p>
                                <p>Producent: <span><?php echo htmlspecialchars($game['developer_name'] ?? 'N/A'); ?></span></p>
                            </div>

                            <div class="featured-price-box">
                                <?php echo $priceDisplay; ?>
                            </div>
                        </div>
                    </a>
                    
                    <?php 
                        $slideIndex++;
                        endwhile; 
                    endif;
                    ?>
                    
                </div>

                <button class="nav-btn next-btn" id="btnNext">&#10095;</button>

            </div>
            
            <div class="carousel-indicators" id="featuredIndicators">
                <?php 
                if ($result && $result->num_rows > 0):
                    $result->data_seek(0);
                    $dotIndex = 0;
                    while ($row = $result->fetch_assoc()): 
                ?>
                    <span class="dot <?php echo $dotIndex === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $dotIndex; ?>)"></span>
                <?php 
                    $dotIndex++;
                    endwhile;
                endif; 
                ?>
            </div>
        </div>

        <div class="tags-container-wrapper">
            <h2 class="section-title">Przeglądaj ulubione kategorie</h2>
            
            <div class="tags-carousel-row">
            <button class="nav-btn prev-btn" id="btnPrevTag">&#10094;</button>

                <div class="tags-scene">
                    <div class="tags-stage" id="tagsStage">
                        <?php 
                        if ($resultTags && $resultTags->num_rows > 0):
                            $index = 0;
                            while ($tag = $resultTags->fetch_assoc()):
                                $tagName = $tag['name'];
                                $safeTagName = strtolower(str_replace(' ', '_', $tagName));
                                $tagImg = "img/others/tags/{$safeTagName}.jpg";
                                
                                if(!file_exists($tagImg)) {
                                    $tagImg = "img/others/loginBackground.jpg"; 
                                }
                        ?>
                        <a href="store.php?tag=<?php echo urlencode($tagName); ?>" class="tag-tile-3d" data-index="<?php echo $index; ?>">
                            <img src="<?php echo $tagImg; ?>" alt="<?php echo htmlspecialchars($tagName); ?>" class="tag-img">
                            <div class="tag-gradient-overlay"></div>
                            <div class="tag-label-box">
                                <span class="tag-name"><?php echo htmlspecialchars($tagName); ?></span>
                            </div>
                        </a>
                        <?php 
                                $index++;
                            endwhile;
                        endif;
                        ?>
                    </div>
                </div>

                <button class="nav-btn next-btn" id="btnNextTag">&#10095;</button>
            </div>

            <div class="carousel-indicators" id="tagIndicators">
            </div>
        </div>

        <div class="browse-platforms-container">
            <h2 class="section-title">Graj na wybranych platformach</h2>
            
            <div class="platforms-grid">
                <a href="store.php?platform=Windows" class="platform-nav-btn">
                    <span class="platform-icon-wrapper platform-item" data-platform-name="windows"></span>
                    <span class="platform-label">WINDOWS</span>
                </a>
                
                <a href="store.php?platform=Playstation" class="platform-nav-btn">
                    <span class="platform-icon-wrapper platform-item" data-platform-name="playstation"></span>
                    <span class="platform-label">PLAYSTATION</span>
                </a>

                <a href="store.php?platform=Xbox" class="platform-nav-btn">
                    <span class="platform-icon-wrapper platform-item" data-platform-name="xbox"></span>
                    <span class="platform-label">XBOX</span>
                </a>

                <a href="store.php?platform=Nintendo" class="platform-nav-btn">
                    <span class="platform-icon-wrapper platform-item" data-platform-name="nintendo"></span>
                    <span class="platform-label">NINTENDO</span>
                </a>
            </div>
        </div>

        <div class="featured-container" id="discountContainer">
            <h2 class="section-title">Przeceny</h2>
            
            <div class="carousel-wrapper">
                
                <button class="nav-btn prev-btn" id="btnPrevDiscount">&#10094;</button>

                <div class="featured-carousel" id="discountCarousel">
                    
                    <?php 
                    $slideIndexDiscount = 0;
                    if ($resultDiscount && $resultDiscount->num_rows > 0):
                        while ($game = $resultDiscount->fetch_assoc()): 
                            
                            $baseName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($game['name']));
                            $img1 = "img/games/{$baseName}_1.jpg";
                            $img2 = "img/games/{$baseName}_2.jpg";
                            $img3 = "img/games/{$baseName}_3.jpg";
                            $img4 = "img/games/{$baseName}_4.jpg";
                            
                            if(!file_exists($img1)) $img1 = "img/others/loginBackground.jpg"; 
                            
                            $price = $game['price'];
                            $discount = $game['discount_percent'];
                            
                            $discountedPrice = $price * (1 - $discount / 100);
                            $priceDisplay = '
                                <div class="discount-block">
                                    <span class="discount-badge">-' . $discount . '%</span>
                                    <div class="price-stack">
                                        <span class="old-price">' . number_format($price, 2) . ' zł</span>
                                        <span class="current-price">' . number_format($discountedPrice, 2) . ' zł</span>
                                    </div>
                                </div>';
                    ?>
                    
                    <a href="game.php?game=<?php echo urlencode($game['name']); ?>" class="featured-item <?php echo $slideIndexDiscount === 0 ? 'active' : ''; ?>" data-index="<?php echo $slideIndexDiscount; ?>">
                        
                        <div class="featured-left">
                            <img src="<?php echo $img1; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="main-featured-img">
                        </div>

                        <div class="featured-right">
                            <h3 class="featured-title"><?php echo htmlspecialchars($game['name']); ?></h3>
                            
                            <div class="featured-thumbs-grid">
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img1; ?>')"><img src="<?php echo $img1; ?>" alt=""></div>
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img2; ?>')"><img src="<?php echo $img2; ?>" alt=""></div>
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img3; ?>')"><img src="<?php echo $img3; ?>" alt=""></div>
                                <div class="thumb-box" onmouseover="updateMainImage(this, '<?php echo $img4; ?>')"><img src="<?php echo $img4; ?>" alt=""></div>
                            </div>

                            <div class="featured-meta">
                                <p>Wydawca: <span><?php echo htmlspecialchars($game['publisher_name'] ?? 'N/A'); ?></span></p>
                                <p>Producent: <span><?php echo htmlspecialchars($game['developer_name'] ?? 'N/A'); ?></span></p>
                            </div>

                            <div class="featured-price-box">
                                <?php echo $priceDisplay; ?>
                            </div>
                        </div>
                    </a>
                    
                    <?php 
                        $slideIndexDiscount++;
                        endwhile; 
                    endif;
                    ?>
                    
                </div>

                <button class="nav-btn next-btn" id="btnNextDiscount">&#10095;</button>

            </div>
            
            <div class="carousel-indicators" id="discountIndicators">
                <?php 
                if ($resultDiscount && $resultDiscount->num_rows > 0):
                    $resultDiscount->data_seek(0);
                    $dotIndex = 0;
                    while ($row = $resultDiscount->fetch_assoc()): 
                ?>
                    <span class="dot <?php echo $dotIndex === 0 ? 'active' : ''; ?>" onclick="currentSlideDiscount(<?php echo $dotIndex; ?>)"></span>
                <?php 
                    $dotIndex++;
                    endwhile;
                endif; 
                ?>
            </div>
        </div>

    </div>

    <?php include('footer.php'); ?>
    <script src="js/index.js"></script>
</body>
</html>