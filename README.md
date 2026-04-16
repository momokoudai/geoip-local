# GeoIP Local

为 Flarum 论坛提供本地化 IP 地理位置解析功能，支持 MaxMind GeoLite2、DB-IP、IP2Location 等数据库。

## 特性

- 🌍 **多数据库支持**：兼容 MaxMind GeoLite2-City/Country、DB-IP、IP2Location 格式
- 🇨🇳 **中国地区优化**：自动显示省份中文名（如"山东"），港澳台特殊标注（"中国香港"等）
- 🔒 **权限集成**：完全复用 FoF GeoIP 的权限控制（`showIPCountry` / `canSeeCountry`）
- ⚡ **高性能**：本地数据库解析，无外部 API 依赖
- 🔄 **自动更新**：支持定时任务或手动一键更新数据库

## 安装

```bash
composer require momokoudai/geoip-local
```

## 配置

### 1. 准备数据库文件

#### 方式一：自动下载（推荐）

在管理后台启用"自动更新数据库"选项。若使用 MaxMind 官方源，需填写 Account ID 和 License Key；也可填入自定义下载地址（如 jsdelivr CDN）。

#### 方式二：手动上传

1. 下载以下任一数据库文件：
   - [MaxMind GeoLite2-City.mmdb](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data)
   - [DB-IP City Lite](https://db-ip.com/db/download/ip-to-city-lite)
   - [IP2Location BIN](https://lite.ip2location.com/)
2. 在管理后台“工具”栏目中上传文件，或直接放入 `storage/geoip/` 目录。

### 2. 选择数据库类型

在管理后台选择对应的数据库驱动：

- **MaxMind MMDB**：适用于 `.mmdb` 格式（GeoLite2、DB-IP）
- **IP2Location BIN**：适用于 `.bin` 格式

### 3. 配置权限

本扩展复用 [FoF GeoIP](https://github.com/FriendsOfFlarum/geoip) 的权限体系：

- 确保已安装 `fof/geoip` 扩展
- 在用户组权限中配置
- 拥有权限的用户将看到地理位置信息

## 使用

安装完成后，帖子头部和用户卡片会自动显示发帖人的地理位置：

- **中国大陆用户**：显示省份名称（如"山东"、"北京"）
- **港澳台用户**：显示"中国香港"、"中国澳门"、"中国台湾"
- **海外用户**：显示国家名称（如"美国"、"日本"）

## 技术细节

### 数据库精度

| 数据库类型       | 精度             | 适用场景             |
| ---------------- | ---------------- | -------------------- |
| GeoLite2-City    | 城市级（含省份） | 推荐，可显示中国省份 |
| GeoLite2-Country | 国家级           | 基础需求             |
| DB-IP City Lite  | 城市级           | 免费替代方案         |
| IP2Location      | 取决于购买版本   | 商业高精度需求       |

### 路径配置说明

- **相对路径**：若在设置中填写 `storage/geoip/xxx.mmdb`，系统会自动将其解析为 Flarum 根目录下的绝对路径。
- **绝对路径**：直接填写完整路径（如 `/var/www/flarum/storage/...`）可避免工作目录不同导致的读取错误。

### 与 FoF GeoIP 共存

本扩展可与 FoF GeoIP 同时启用：

- **数据显示**：由 GeoIP Local 提供（本地解析）
- **权限控制**：由 FoF GeoIP 管理
- **数据存储**：FoF GeoIP 仍会存储其解析结果（可能来自外部 API）

> ⚠️ **注意**：若希望数据来源统一，建议在 FoF GeoIP 设置中选择 "geoip-local" 作为数据源。

## 常见问题

### Q: 为什么不显示地理位置？

A: 检查以下几点：

1. 确认已上传有效的数据库文件
2. 查看 `storage/logs/flarum.log` 是否有解析错误
3. 确认当前用户组拥有查看地理位置的权限

### Q: 如何更新数据库？

A:

- **自动更新**：启用定时任务后，系统会根据设定的频率（默认 24 小时）自动检查并更新。
- **手动更新**：在管理后台点击“立即更新”按钮。

### Q: 支持哪些 IP 版本？

A: 同时支持 IPv4 和 IPv6（需数据库包含对应数据）。

## 许可证

MIT License。详见 [LICENSE](LICENSE) 文件。
