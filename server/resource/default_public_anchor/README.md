# 默认公共形象母版资源

母版文件放在项目 `public/static/...` 目录，与 `config/api_tools.php` 中 `default_public_anchor.files` 列表一一对应。

## 目录结构

```
public/
  static/images/default_ai_personal_avatar.jpg
  static/videos/default_ai_personal_anchor.mp4
  static/audio/default_shanjian_voice.mp3
  static/videos/default_ai_personal_auth_anchor.mp4
```

## 部署步骤

1. 将母版媒体文件按上述路径放入 `public/static/...`。
2. 确认 `config/api_tools.php` 中 `default_public_anchor` 段的相对路径与第三方 ID 正确。
3. 每个站长在定时任务 `ai_digital_human_anchor_cron` 首次运行时会自动同步一次（OSS 站长上传到相同相对路径）；也可手动执行：

```bash
php think sync_default_public_anchor_assets
php think sync_default_public_anchor_assets --force
```

本地存储站长无需上传，文件已在 `public` 目录即可使用。
