<?php
namespace app\admin\controller;

use app\admin\BaseAdmin;
use think\facade\Filesystem;

class Upload extends BaseAdmin
{
    public function image()
    {
        $file = $this->request->file('file');
        if (!$file) return $this->error('请选择文件');

        try {
            $uploadPath = Filesystem::disk('public')->putFile('images', $file, function () {
                return date('Ymd') . '/' . md5(uniqid());
            });
            $url = '/uploads/' . str_replace('\\', '/', $uploadPath);
            return $this->success(['url' => $url, 'path' => $uploadPath], '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败: ' . $e->getMessage());
        }
    }

    public function file()
    {
        $file = $this->request->file('file');
        if (!$file) return $this->error('请选择文件');
        
        try {
            $uploadPath = Filesystem::disk('public')->putFile('files', $file);
            $url = '/uploads/' . str_replace('\\', '/', $uploadPath);
            return $this->success(['url' => $url, 'path' => $uploadPath], '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败');
        }
    }

    public function delete()
    {
        $path = $this->request->param('path', '');
        $filePath = ROOT_PATH . 'public/uploads/' . $path;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        return $this->success([], '删除成功');
    }
}
