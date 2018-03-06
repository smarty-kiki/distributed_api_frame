<?php

if_get('/', function ()
{
    $o = qc_goods_order_dao::find(1);

    return 'hello world';
});
