<?php
// 设置响应头为JSON格式（仅用于调试API响应）
// header('Content-Type: application/json');

// 处理字体搜索
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 0;
$results = [];
$hasMore = false;

// 处理字体详情请求
$detailData = null;
if (isset($_GET['detail'])) {
    $packId = $_GET['detail'];
    $detailData = apiRequest("https://api.zhuti.intl.xiaomi.com/app/v9/uipages/theme/" . urlencode($packId));
}

if (!empty($keyword) && !isset($_GET['detail'])) {
    $apiUrl = "https://thm.market.intl.xiaomi.com/thm/search/npage?category=Font&keywords=" . urlencode($keyword) . "&page=" . $page;
    $data = apiRequest($apiUrl);
    
    if (isset($data['apiData']['cards'][0]['items'])) {
        $results = $data['apiData']['cards'][0]['items'];
        $hasMore = isset($data['apiData']['hasMore']) && $data['apiData']['hasMore'];
    }
}

/**
 * 直接发起API请求（无缓存）
 * @param string $url API地址
 * @return mixed API响应数据
 */
function apiRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>主题字体搜索</title>
    <style>
        body {
            font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .search-container {
            max-width: 800px;
            margin: 0 auto 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-btn {
            padding: 10px 20px;
            background-color: #ff6700;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .search-btn:hover {
            background-color: #ff4500;
        }
        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .font-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }
        .font-card:hover {
            transform: translateY(-5px);
        }
        .font-image {
            width: 100%;
            height: 180px;
            object-fit: contain;
            background-color: #EFEFF7;
        }
        .font-info {
            padding: 15px;
        }
        .font-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .font-price {
            color: #ff6700;
            font-size: 14px;
        }
        .no-results {
            text-align: center;
            color: #666;
            padding: 40px 0;
            grid-column: 1 / -1;
        }
        .detail-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .detail-title {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        .detail-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-image {
            width: 100%;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .detail-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: #666;
        }
        .download-btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #ff6700;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .download-btn:hover {
            background-color: #ff4500;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #333;
        }
        .pagination {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 20px;
        }
        .next-page-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ff6700;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .next-page-btn:hover {
            background-color: #ff4500;
        }
        .current-page {
            margin: 0 15px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php if (!isset($_GET['detail'])): ?>
        <div class="search-container">
            <form class="search-form" method="GET">
                <input 
                    type="text" 
                    class="search-input" 
                    name="keyword" 
                    placeholder="输入字体名称，如'XY102'" 
                    value="<?php echo htmlspecialchars($keyword); ?>"
                    required
                >
                <input type="hidden" name="page" value="0">
                <button type="submit" class="search-btn">搜索</button>
            </form>
        </div>

        <div class="results-container">
            <?php if (!empty($keyword)): ?>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results[0]['schema']['clicks'] as $font): ?>
                        <?php 
                        $imageUrl = $font['root'] . $font['pic'];
                        $title = $font['title'] ?? '未知字体';
                        $price = $font['extra']['productPrice'] == 0 ? '免费' : ('¥' . $font['extra']['productPrice']);
                        $link = $font['link'];
                        ?>
                        <div class="font-card" onclick="window.location.href='?detail=<?php echo urlencode($link); ?>'">
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="font-image">
                            <div class="font-info">
                                <div class="font-title"><?php echo htmlspecialchars($title); ?></div>
                                <div class="font-price"><?php echo htmlspecialchars($price); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($hasMore): ?>
                        <div class="pagination">
                            <span class="current-page">当前第 <?php echo $page + 1; ?> 页</span>
                            <a href="?keyword=<?php echo urlencode($keyword); ?>&page=<?php echo $page + 1; ?>" class="next-page-btn">加载更多结果</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>没有找到与"<?php echo htmlspecialchars($keyword); ?>"相关的字体</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if ($detailData && $detailData['apiCode'] == '0'): ?>
            <?php 
            $themeDetail = $detailData['apiData']['extraInfo']['themeDetail'];
            $name = $themeDetail['name'];
            $downloadUrl = $themeDetail['downloadUrl'];
            $fullDownloadUrl = "https://f17.market.xiaomi.com/issue/ThemeMarket/{$downloadUrl}/{$name}.mtz";
            $description = $themeDetail['description'] ?? '暂无描述';
            $snapshots = $themeDetail['snapshotsUrl'] ?? [];
            ?>
            <div class="detail-container">
                <h1 class="detail-title"><?php echo htmlspecialchars($name); ?></h1>
                
                <?php if (!empty($snapshots)): ?>
                    <div class="detail-images">
                        <?php foreach ($snapshots as $k => $snapshot): 
                        if ($k >= 2) break;
                        ?>
                            <img src="https://t17.market.mi-img.com/thumbnail/webp/w1080q70/<?php echo htmlspecialchars($snapshot); ?>" class="detail-image">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="detail-description">
                    <?php echo nl2br(htmlspecialchars($description)); ?>
                </div>
                
                <a href="<?php echo htmlspecialchars($fullDownloadUrl); ?>" class="download-btn">下载字体文件 (.mtz)</a>
                <br>
                <a href="?keyword=<?php echo urlencode($keyword); ?>" class="back-link">← 返回搜索结果</a>
            </div>
        <?php else: ?>
            <div class="detail-container">
                <h1 class="detail-title">获取字体详情失败</h1>
                <p>无法获取字体详情信息，请稍后再试。</p>
                <a href="?keyword=<?php echo urlencode($keyword); ?>" class="back-link">← 返回搜索结果</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>