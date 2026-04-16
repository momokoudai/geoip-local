# 前端构建说明

## 为什么不能在此目录直接构建?

这个扩展的前端代码依赖于 Flarum 核心框架的模块和类型定义,包括:
- `flarum/forum/app`
- `flarum/common/extend`
- `flarum/common/components/*`
- `fof/geoip/*`

这些模块只在完整的 Flarum 项目中存在。

## package.json 的作用

当前目录的 `package.json` 和构建配置可用于:
1. **IDE 支持** - TypeScript 智能提示和类型检查
2. **开发参考** - 了解所需的依赖版本
3. **文档目的** - 说明项目的技术栈
4. **独立构建** - 使用 IgnorePlugin 忽略 Flarum 依赖进行语法检查和打包(仅用于开发调试)

## 正确的构建流程

### 对于扩展用户(安装使用)

不需要手动构建!Composer 安装后,Flarum 会自动处理:

```bash
composer require momokoudai/geoip-local
php flarum cache:clear
```

Flarum 会在首次访问或运行 `npm run build` 时自动编译扩展的前端资源。

### 对于扩展开发者(修改代码)

#### 方案 A:在 Flarum 项目中开发(推荐)

1. 将扩展链接到 Flarum 项目:
```bash
# 在 Flarum 根目录
composer config repositories.geoip-local path /path/to/geoip-local
composer require momokoudai/geoip-local:@dev
```

2. 在 Flarum 根目录启动开发服务器:
```bash
npm run dev
```

这会自动监听所有扩展的文件变化并重新编译。

#### 方案 B:独立构建(仅用于开发调试)

如果需要在没有 Flarum 环境的情况下快速检查语法和打包:

```bash
cd js
npm install
npm run build
```

**注意:**
- 此方式使用 webpack 的 `IgnorePlugin` 忽略所有 Flarum 和 FoF 模块
- 生成的代码**不能在浏览器中直接运行**,仅用于验证语法正确性
- 最终部署前必须在 Flarum 环境中重新构建

#### 方案 C:使用 Flarum CLI

如果安装了 Flarum CLI:
```bash
flarum-cli build
```

## 技术细节

### 文件映射关系

```
js/admin.ts          -> dist/admin.js (管理后台)
js/forum.ts         -> dist/forum.js (论坛前台)
resources/less/*.less -> 编译后的 CSS
```

### 构建产物位置

编译后的文件会生成在:
- `js/dist/admin.js`
- `js/dist/forum.js`

但这些文件应该**提交到版本控制**,因为:
1. 方便用户直接使用,无需构建步骤
2. Flarum 扩展通常包含预编译的资源
3. 避免用户需要 Node.js 环境

### 独立构建的配置

webpack.config.js 使用了以下策略实现独立构建:

```javascript
plugins: [
  // 忽略所有 flarum 和 fof 导入
  new webpack.IgnorePlugin({
    resourceRegExp: /^(flarum|fof)\//,
  }),
]
```

这使得 TypeScript 代码可以被编译成 JavaScript,但生成的代码缺少 Flarum 运行时依赖。

## 常见问题

### Q: 我修改了 TypeScript 代码,但前台没有变化?

A: 需要重新构建:
1. 在 Flarum 根目录运行 `npm run build`
2. 清除缓存:`php flarum cache:clear`
3. 硬刷新浏览器(Ctrl+F5)

### Q: 如何调试前端代码?

A: 
1. 使用 `npm run dev` 启用 source map
2. 浏览器开发者工具中可以看到原始 TypeScript 代码
3. 可以在 Flarum 管理后台禁用缓存

### Q: TypeScript 报错怎么办?

A: 
1. 确保 IDE 正确识别了 tsconfig.json
2. 某些 Flarum 模块可能没有类型定义,可以使用 `// @ts-ignore`
3. 参考其他 Flarum 扩展的代码风格

### Q: npm run build 失败,提示找不到 flarum 模块?

A: 
- **在 Flarum 项目中构建**:切换到 Flarum 根目录,使用 Flarum 的构建系统
- **独立构建**:当前的 webpack.config.js 已配置 IgnorePlugin,可以直接运行 `npm run build`,但生成的代码仅供语法检查

### Q: 为什么有些文件是 .tsx 而不是 .ts?

A: 
包含 JSX 语法的文件必须使用 `.tsx` 扩展名,例如:
- `addLocationToPostMeta.tsx`
- `addLocationToPostHeader.tsx`
- `addLocationToUserCard.tsx`

TypeScript 编译器需要 `.tsx` 扩展名才能正确解析 JSX 语法。

## 总结

**推荐做法**:在 Flarum 项目根目录进行构建,让 Flarum 的构建系统统一处理所有扩展的资源。

**快速检查**:可以使用独立构建(`cd js && npm run build`)快速验证 TypeScript 语法,但最终部署前必须在 Flarum 环境中重新构建。
