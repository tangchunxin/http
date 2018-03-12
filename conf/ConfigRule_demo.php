<?php
namespace bigcat\conf;

class ConfigRule{
const RULE = 
[
    [
        "rule" => [
          "game_type" => [
            "rulename" => 262,
            "display" => "血战到底"
          ],
          "player_count" => [1],
          "set_num" => [0],
          "min_fan" => 0,
          "top_fan" => [2],

          "zimo_rule" => [3],
          "dian_gang_hua"  =>[4,0],
          "is_change_3"  =>[4,1],
          "is_yaojiu_jiangdui"  =>[4,2],

          "is_menqing_zhongzhang"  =>[4,3],
          "is_tiandi_hu"  =>[4,4],
        
          "pay_type" => [5],
        ],
        "parent" => [0],
        "groups" => [
          [
            "type" => 1, "label" => "局数", "keys" => ["4局(2钻)", "8局(3钻)", "16局(6钻)"],
            "defs" => [4, 8, 16], "values" => [16], "div" => 3
          ],
          [
            "type" => 1, "label" => "人数", "keys" => ["2人", "3人", "4人"],
            "defs" => [2, 3, 4], "values" => [4], "div" => 3
          ],
          [
            "type" => 1, "label" => "封顶", "keys" => ["2番", "3番", "4番", "无限番"],
            "defs" => [2, 3, 4, 0], "values" => [0], "div" => 4
          ],
          [
            "type" => 1, "label" => "玩法", "keys" => ["自摸加底", "自摸加番"],
            "defs" => [0,1], "values" => [1], "div" => 2
          ],
          [
            "type" => 2, "label" => "规则", "keys" => ["点杠花","换三张","幺九将对","门清中张","天地胡"],
            "defs" => [ 1, 1, 1, 1, 1], "values" => [ 1, 1, 1, 1, 1], "div" => 3
          ],
          
          [
            "type" => 1, "label" => "付费", "keys" => ["房主","AA","大赢家","公会付费"],
            "defs" => [0, 1, 2, 3], "values" => [0], "div" => 4
          ]
        ],
        "relation" => [],
        "ex" => [
          "currency" => [2, 3, 6],
          "playerCountRow" => 1,
          "currencyRow" => 0,
          "pay_typeRow" => 5,
          "pay_type" => ["aa" => 1, "agent" => 3],
        ],
        "desc" =>XuezhanDesc::desc
    ]

];



}