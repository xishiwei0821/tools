<?php

declare(strict_types=1);

namespace Shiwei\Tools;

/**
 *  Redis类
 *  @author ShiweiXi <xishiwei0821@gmail.com>
 */
class Redis
{
    protected $host;

    protected $port;

    protected $select;

    protected $password;

    protected $prefix;

    public $redis;

    public function __construct(array $options = [])
    {
        $this->host     = array_key_exists('host', $options)     ? $options['host']     : '127.0.0.1';
        $this->port     = array_key_exists('post', $options)     ? $options['post']     : 6379;
        $this->select   = array_key_exists('select', $options)   ? $options['select']   : 0;
        $this->password = array_key_exists('password', $options) ? $options['password'] : '';
        $this->prefix   = array_key_exists('prefix', $options)   ? $options['prefix']   : '';

        $this->initRedis();
    }

    /**
     *  初始化redis链接
     */
    protected function initRedis()
    {
        $this->redis = new \Redis();

        if (!$this->redis->connect($this->host, $this->port)) {
            throw new \Exception('redis连接失败，请检查是否启用redis');
        }

        if (!empty($this->password)) $this->redis->auth($this->password);

        $this->redis->select((int)$this->select);
    }

    /**
     *  保存key->value类型数据
     *  @param string $key  # 保存键
     *  @param string $value # 保存值
     *  @param int    $expire #过期时间，可选
     */
    public function set($key, $value, $expire = 0)
    {
        if (empty($key)) throw new \Exception('key不存在');

        if (empty($value)) throw new \Exception('value不存在');

        if ($expire == 0) {
            $this->redis->set($this->prefix . $key, $value);
        } else {
            $this->redis->set($this->prefix . $key, $value, $expire);
        }
    }

    /**
     *  获取key->value类型数据值
     *  @param string $key  # 键
     *  @return string $value # 返回值
     */
    public function get($key)
    {
        if (empty($key)) throw new \Exception('key不存在');

        if (!$this->redis->exists($this->prefix . $key)) {
            return ''; 
        }

        return $this->redis->get($this->prefix . $key);
    }

    /**
     *  删除key->value类型数据值
     *  @param string $key # 键
     */
    public function stringDel($key)
    {
        if (empty($key)) throw new \Exception('key不存在');

        if (is_array($key)) {
            foreach ($key as $field) {
                $this->stringDel($this->prefix . $field);
            }
        } else {
            if ($this->redis->exists($this->prefix . $key)) {
                $this->redis->del($this->prefix . $key);
            }
        }
    }

    /**
     *  保存hash类型数据
     * 
     *  @param string $key 键
     * 
     *  =========保存单条数据=========
     *  @param string|array   $field 字段名
     *  @param string|integer $value 值
     * 
     *  =========保存多条数据=========
     *  @param array $array
     */
    public function hashSet($key, $field, $value)
    {
        if (empty($key)) throw new \Exception('请设置保存key');
        if (empty($field)) throw new \Exception('请设置保存字段');

        if (is_array($field)) {
            $this->redis->hmset($this->prefix . $key, $field);
        } else {
            $this->redis->hset($this->prefix . $key, $field, $value);
        }
    }

    /**
     *  获取hash类型数据
     *  @param string $key 键
     *  @param string|array $fields 字段名
     *  @return string|array|null
     */
    public function hashGet($key, $fields)
    {
        if (empty($key)) return null;
        if (empty($fields)) return null;

        if (is_array($fields)) {
            return $this->redis->hmget($this->prefix . $key, $fields);
        } else {
            return $this->redis->hget($this->prefix . $key, $fields);
        }
    }

    /**
     *  删除hash类型数据
     *  @param string $key 键
     *  @param string|array $field 字段名
     */
    public function hashDel($key, $fields) {
        if (empty($key)) throw new \Exception('key不存在');

        if (empty($fields)) throw new \Exception('fields不存在');

        if (is_array($fields)) {
            foreach ($fields as $field) {
                $this->hashDel($this->prefix . $key, $field);
            }
        } else {
            if ($this->redis->hexists($this->prefix . $key, $fields)) {
                $this->redis->hdel($this->prefix . $key, $fields);
            }
        }
    }

    /**
     *  添加set类型数据
     *  @param string $key 键
     *  @param string|array $value 值
     */
    public function setSet($key, $values)
    {
        if (empty($key)) throw new \Exception('键不存在');

        if (empty($values)) throw new \Exception('值不存在');

        return $this->redis->sadd($this->prefix . $key, $values);
    }

    /**
     *  获取set类型数据
     *  @param string $key 键
     *  @return array $value 值
     */
    public function setGet($key)
    {
        if (empty($key)) throw new \Exception('键不存在');

        $members = $this->redis->smembers($this->prefix . $key);
        $count = $this->redis->scard($this->prefix . $key);

        return compact('count', 'members');
    }

    /**
     *  删除set类型数据
     *  @param string $key 键
     *  @param string|array $value 要移除的元素
     */
    public function setDel($key, $value)
    {
        if (empty($key)) throw new \Exception('键不存在');

        if (is_array($value)) {
            foreach ($value as $field) {
                $this->setDel($key, $field);
            }
        } else {
            $this->redis->srem($this->prefix . $key, $value);
        }
    }

    /**
     *  清空set类型数据
     *  @param string $key 键
     */
    public function setRem($key)
    {
        if (empty($key)) throw new \Exception('键不存在');

        $fields = $this->setGet($key);
        if ($fields['count'] != 0) {
            foreach ($fields['members'] as $member) {
                $this->setDel($key, $member);
            }
        }
    }

    /**
     *  列表新增
     */
    public function listPush($key, $value, $type = 'r')
    {
        $methods = [
            'r' => 'rpush',
            'l' => 'lpush'
        ];

        if (empty($key)) throw new \Exception('键不存在');

        if (empty($value)) throw new \Exception('值不存在');
        
        $type = empty($type) || !in_array($type, array_keys($methods)) ? 'r' : $type;

        $method = $methods[$type];

        if (is_array($value)) {
            return $this->redis->$method($this->prefix . $key, ...$value);
        } else {
            return $this->redis->$method($this->prefix . $key, $value);
        }
    }

    /**
     *  列表弹出
     *  @param mixed $key
     *  @return void
     */
    public function listPop($key, $number = 1)
    {
        $methods = [
            'r' => 'rpop',
            'l' => 'lpop'
        ];

        $number = !empty($number) ? $number : 1;

        if (empty($key)) throw new \Exception('键不存在');

        $type = empty($type) || !in_array($type, array_keys($methods)) ? 'r' : $type;

        $method = $methods[$type];

        return $this->redis->$method($key, $number);
    }
}
