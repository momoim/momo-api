## MOMO API

## docker+git发布版本

### 开发

- 本地安装docker和docker-compose
    
    linux参考[docker](www.docker.com)官网根据平台安装docker, windows和mac可以安装dockertoolbox
    
    docker-compose 可以用`pip install docker-compose`安装或者参考官网安装

- 启动服务
    `docker-compose -f docker-compose-dev.yml up`
    
- 容器带ssh,可以用帐号root密码password进入容器

### 部署

- 迁出代码到本地

    `git clone https://github.com/momoim/momo-api.git`

- 本机安装fabric依赖

    `pip install fabric`

- 在deploy目录根据实际情况修改fabfile.py文件， 然后运行

    `fab prepare`  # 如果一台服务器部署多个分支,默认只部署第一个分支
    
    如果需要指定角色/分支可以加参数
    
    `fab prepare:roles=staging`

- 之后要部署到主机别名为vagrant的staging只要执行

    `git push vagrant staging`

- 在deploy目录回滚到上次发布
    `fab rollback`
