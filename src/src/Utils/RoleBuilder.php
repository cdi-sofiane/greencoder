<?php

namespace App\Utils;


class RoleBuilder
{

  public static function defaultRole()
  {
    return [
      "admin" => [
        "dashboard",
        "profile",
        "video_library",
        "video_detail",
        "video_stream",
        "video_download",
        "video_delete",
        "video_encode",
        "video_recode",
        "account_invite",
        "report_encode",
        "report_config",
        "account_invoice",
        "account_payment",
      ],
      "editor" => [
        "dashboard",
        "profile",
        "video_library",
        "video_detail",
        "video_stream",
        "video_download",
        "video_delete",
        "video_encode",
        "video_recode",
        "account_invite",
        "report_encode",
        "report_config",
      ],
      "reader" => [
        "dashboard",
        "profile",
        "video_library",
        "video_detail",
        "video_stream",
        "video_download",
      ]
    ];
  }
}
