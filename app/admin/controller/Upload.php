<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Filesystem;

class Upload extends BaseAdmin
{
    // 图片允许的 MIME 类型白名单
    private $imageMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'image/bmp', 'image/svg+xml',
    ];
    // 图片允许的扩展名白名单
    private $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    // 文件允许的 MIME 类型白名单（文档类）
    private $fileMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
    ];
    // 文件允许的扩展名白名单
    private $fileExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'csv'];

    // 最大文件大小：图片 10MB，文件 20MB
    private $imageMaxSize = 10485760;  // 10MB
    private $fileMaxSize  = 20971520;  // 20MB

    public function image()
    {
        $file = $this->request->file('file');
        if (!$file) return $this->error('请选择文件');

        // 校验 MIME 类型
        $mime = $file->getMime();
        if (!in_array($mime, $this->imageMimeTypes)) {
            return $this->error('不允许的文件类型: ' . $mime);
        }

        // 校验扩展名
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, $this->imageExtensions)) {
            return $this->error('不允许的文件扩展名: ' . $ext);
        }

        // 校验文件大小
        $_size = $file->getSize();
        if ($_size > $this->imageMaxSize) {
            return $this->error('图片大小不能超过 10MB');
        }

        try {
            // 图片内容二次校验：防止恶意文件伪造 MIME 类型
            $tmpPath = $file->getPathname();
            $imgInfo = @getimagesize($tmpPath);
            if (!$imgInfo) {
                return $this->error('文件不是有效的图片');
            }
            // 校验实际图片类型是否与声明一致
            $realMime = $imgInfo['mime'] ?? '';
            if (!in_array($realMime, $this->imageMimeTypes)) {
                return $this->error('图片内容类型不合法: ' . $realMime);
            }

            $uploadPath = Filesystem::disk('public')->putFile('images', $file, function () {
                return date('Ymd') . '/' . md5(uniqid() . mt_rand());
            });
            $uploadPath = str_replace('\\', '/', $uploadPath);
            $url = '/uploads/' . $uploadPath;
            return $this->success(['url' => $url, 'path' => $uploadPath], '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败');
        }
    }

    public function file()
    {
        $file = $this->request->file('file');
        if (!$file) return $this->error('请选择文件');

        // 校验 MIME 类型
        $mime = $file->getMime();
        if (!in_array($mime, $this->fileMimeTypes)) {
            return $this->error('不允许的文件类型: ' . $mime);
        }

        // 校验扩展名
        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, $this->fileExtensions)) {
            return $this->error('不允许的文件扩展名: ' . $ext);
        }

        // 校验文件大小
        $_size = $file->getSize();
        if ($_size > $this->fileMaxSize) {
            return $this->error('文件大小不能超过 20MB');
        }

        try {
            $uploadPath = Filesystem::disk('public')->putFile('files', $file, function () {
                return date('Ymd') . '/' . md5(uniqid() . mt_rand());
            });
            $uploadPath = str_replace('\\', '/', $uploadPath);
            $url = '/uploads/' . $uploadPath;
            return $this->success(['url' => $url, 'path' => $uploadPath], '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败');
        }
    }

    public function delete()
    {
        $path = $this->request->param('path', '');

        // 路径遍历防护：禁止 ../ 和 \\
        if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
            return $this->error('非法路径');
        }

        // 只允许删除 uploads 目录下的文件
        $realPath = realpath(ROOT_PATH . 'public/uploads/' . $path);
        $uploadDir = realpath(ROOT_PATH . 'public/uploads');

        if (!$realPath || !$uploadDir || strpos($realPath, $uploadDir) !== 0) {
            return $this->error('非法文件路径');
        }

        // 禁止删除目录
        if (is_dir($realPath)) {
            return $this->error('不能删除目录');
        }

        if (file_exists($realPath)) {
            @unlink($realPath);
            return $this->success([], '删除成功');
        }

        return $this->success([], '文件不存在');
    }
}
