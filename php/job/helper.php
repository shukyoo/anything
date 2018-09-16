<?php namespace Job;

function getShopCode($shop_id)
{
    if (!$shop_id) {
        return '';
    }

    // 尝试从缓存里获取 shop_id => shop_code
    $key = 'job_shop_id_code';
    $shops = Cache::getData($key, function(){
        return DbQuery::fetchPairs('SELECT shop_id, shop_code FROM shops');
    }, 86400);

    return (isset($shops[$shop_id]) ? $shops[$shop_id] : '');
}


function money($money)
{
    return number_format($money, 2, '.', '');
}