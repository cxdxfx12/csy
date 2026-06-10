#!/bin/bash
# ============================================
#  Mac 上一键构建4个 iOS IPA
#  把 e:\ds\apps\ 整个目录拷贝到 Mac 上
#  然后在这个目录下运行此脚本
# ============================================
set -e

# 先装 CocoaPods（如果没有）
if ! which pod > /dev/null 2>&1; then
    echo ">>> 安装 CocoaPods..."
    sudo gem install cocoapods
fi

APPS=("admin-app" "owner-app" "staff-app" "manager-app")

for app in "${APPS[@]}"; do
    echo ""
    echo "========================================"
    echo "  构建: $app"
    echo "========================================"
    cd "$app"

    # 安装 npm 依赖
    npm install

    # 同步 iOS 平台（会跑 pod install）
    npx cap sync ios

    cd ios/App

    # pod install
    pod install

    # 构建 archive
    WORKSPACE="App.xcworkspace"
    SCHEME="App"
    ARCHIVE_PATH="../../build/App.xcarchive"

    xcodebuild archive \
        -workspace "$WORKSPACE" \
        -scheme "$SCHEME" \
        -archivePath "$ARCHIVE_PATH" \
        -configuration Release \
        -sdk iphoneos \
        -allowProvisioningUpdates \
        CODE_SIGN_IDENTITY="iPhone Distribution" \
        CODE_SIGN_STYLE="Automatic" \
        | tail -20

    # 导出 IPA
    xcodebuild -exportArchive \
        -archivePath "$ARCHIVE_PATH" \
        -exportPath "../../build" \
        -exportOptionsPlist "../../ExportOptions.plist" \
        -allowProvisioningUpdates \
        | tail -10

    echo ">>> $app.ipa 生成完成!"
    cd ../..
done

echo ""
echo "========================================"
echo "  全部4个 IPA 已生成在各 app 的 build/ 目录"
echo "========================================"
ls -lh */build/*.ipa 2>/dev/null
