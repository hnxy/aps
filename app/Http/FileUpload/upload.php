<?php

namespace App\Http\FileUpload;

use App\Exceptions\FileUploadException;
use App\Http\FileUpload\FileInterface;

class Upload implements FileInterface
{
    public $isMulite;

    protected $extensions = [];

    public $file;

    public $maxFileSize;

    public $defaultPath;

    public function __construct()
    {
        $config = config('file');
        $this->extensions = $config['extensions'];
        $this->defaultPath = $config['path'];
        $this->maxFileSize = $config['max_file_size'];
    }
    //判断文件是否存在
    public function hasFile($name)
    {
        return isset($_FILES[$name]);
    }
    //存贮上传的文件
    public function save($file, $path = null)
    {
        if(!$this->hasFile($file)) {
            throw new FileUploadException("该文件不存在", 1);
        }
        $this->file = $_FILES[$file];
        $this->isMulite = $this->isMulite();
        if ($this->isMulite) {
            return $this->resolveMuliti($path);
        } else {
            return $this->resolveSimple($this->file, $path);
        }
    }
    //判断是不是多文件上传
    public function isMulite()
    {
        return is_array($this->file['name']);
    }
    //处理多文件上传
    protected function resolveMuliti($path)
    {
        $files = [];
        foreach ($this->file['name'] as $index => $file) {
            $files[] = $this->resolveSimple([
                'name' => $this->file['name'][$index],
                'type' => $this->file['type'][$index],
                'tmp_name' => $this->file['tmp_name'][$index],
                'error' => $this->file['error'][$index],
                'size' => $this->file['size'][$index],
            ], $path);
        }
        return $files;
    }
    //获取文件名
    protected function getUniqueName()
    {
        return uniqid();
    }
    //处理单文件
    protected function resolveSimple(array $simpleFile, $path)
    {
        if (($msg = $this->isSuccess($simpleFile['error'])) !== true) {
            throw new FileUploadException($msg, $simpleFile['error']);
        }
        $path = $this->getPath($path);
        $this->isIllegal($simpleFile);
        $path = $this->formatPath($path);
        $filename = $this->getUniqueName(). '.' . $this->getFileExt($simpleFile['name']);
        if (move_uploaded_file($simpleFile['tmp_name'], $path . '/' . $filename)) {
            return [
                'origin_name' => $simpleFile['name'],
                'filename' => $filename,
                'path' => $path,
            ];
        }
        throw new FileUploadException("文件上传失败", 8);
    }
    //格式化路径
    protected function formatPath($path)
    {
        return rtrim($path, '/');
    }
    //判断上传的文件是否合法
    public function isIllegal($simpleFile)
    {
        if(!is_uploaded_file($simpleFile['tmp_name'])) {
            throw new FileUploadException('该文件不是通过http post方式上传的文件', 4);
        }
        if (!$this->inSize($simpleFile)) {
            throw new FileUploadException('文件大小超过设定的值', 5);
        }
        if (!$this->hasExt($simpleFile)) {
            throw new FileUploadException('不支持该扩展名的文件', 6);
        }
        if(!$this->isImage($simpleFile)) {
            throw new FileUploadException('非法的图片文件', 7);
        }
    }
    protected function isImage($file)
    {
        return exif_imagetype($file['tmp_name']) !== false ? true : false;
    }
    //获取默认的路径
    protected function getDefaultPath()
    {
        if (!is_null($this->defaultPath)) {
            return $this->defaultPath;
        }
        // var_dump($this);
        // exit;
        throw new FileUploadException('未设置路径', 8);
    }
    //判断扩展名是不是在要求的范围内
    protected function hasExt($file)
    {
        return in_array($this->getFileExt($file['name']), $this->extensions);
    }
    //判断是否超过设置的大小
    protected function inSize($file)
    {
        return ($file['size'] < $this->maxFileSize) ? true : false;
    }
    //获取文件后缀名
    public function getFileExt($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
    //获取文件路径
    public function getPath($path)
    {
        if (is_null($path)) {
            $path = $this->getDefaultPath();
        }
        $path = str_ireplace('{{Y-m-d}}', date('Y-m-d'), $path);
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
    //判断文件上次是否成功
    public function isSuccess($code)
    {
        switch ($code)
        {
            case '0':
                $msg = true;
                break;
            case '1':
                $msg = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
                break;
            case '2':
                $msg = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
                break;
            case '3':
                $msg = '文件只有部分被上传';
                break;
            case '4':
                $msg = '没有文件被上传';
                break;
            case '6':
                $msg = '找不到临时文件夹';
                break;
            case '7':
                $msg = '文件写入失败';
                break;
        }
        return $msg;
    }
}