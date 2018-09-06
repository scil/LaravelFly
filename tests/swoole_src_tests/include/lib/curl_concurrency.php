<?php

/**
 * multiple curl requests in parallel
 * @example curl_concurrency(['http://g.cn','http://baidu.com'])
 * @example curl_concurrency([
 *  'url'=>'http://g.cn',
 *  'options'=>[
 *     CURLOPT_HEADER => 1
 *   ]
 * ])
 * @author: scil
 */
function curl_concurrency($arrayUrls)
{
    $mh = curl_multi_init();

    foreach ($arrayUrls as $i => $url) {
        $options = [];

        if (is_array($url)) {
            $options = $url['options'] ?? [];
            $url = $url['url'];
        }


        $conn[$i] = curl_init($url);
        curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);

        curl_setopt_array($conn[$i], $options);

        curl_multi_add_handle($mh, $conn[$i]);
    }

    $active = null;

    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($mh) != -1) {
            usleep(100);
        }
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    }


    foreach ($arrayUrls as $i => $url) {
        $res[$i] = curl_multi_getcontent($conn[$i]);
        curl_multi_remove_handle($mh, $conn[$i]);
        curl_close($conn[$i]);
    }

    curl_multi_close($mh);

    return $res;
}
