# config valid only for current version of Capistrano
lock '3.4.0'

set :application, 'Moxie'
set :repo_url, 'git@github.com:hmeza/Moxie'

server 'moxie.dootic.com', roles: %w{web}, my_property: :my_value

# Default branch is :master
# ask :branch, `git rev-parse --abbrev-ref HEAD`.chomp

# Default deploy_to directory is /var/www/my_app_name
set :deploy_to, '/var/www/moxie_app'

# Default value for :scm is :git
# set :scm, :git

# Default value for :format is :pretty
# set :format, :pretty

# Default value for :log_level is :debug
set :log_level, :info

# Default value for :pty is false
# set :pty, true

# Default value for :linked_files is []
# set :linked_files, fetch(:linked_files, []).push('config/database.yml', 'config/secrets.yml')
set :linked_files, %w{application/configs/application.ini phinx.yml}

# Default value for linked_dirs is []
# set :linked_dirs, fetch(:linked_dirs, []).push('log', 'tmp/pids', 'tmp/cache', 'tmp/sockets', 'vendor/bundle', 'public/system')
set :linked_dirs, %w{ application/3rdparty/simple-php-captcha }

# Default value for default_env is {}
# set :default_env, { path: "/opt/ruby/bin:$PATH" }

# Default value for keep_releases is 5
# set :keep_releases, 3

set :stages, ["production"]
set :default_stage, "production"

set :ssh_options, {:compression => "none"}

namespace :deploy do
  desc 'Installing composer modules'
  task :composer_install do
    on roles(:all) do
        within release_path do
            execute "cd #{release_path} && composer install --no-dev"
        end
    end
  end

  desc 'Installing bower modules'
  task :bower_install do
    on roles(:all) do
        within release_path do
            execute "cd #{release_path} && npm install --no-dev"
            execute "cd #{release_path} && bower install"
        end
    end
  end

  desc 'Executing database migrations'
  task :database_update do
    on roles(:all) do
        execute "cd #{release_path} && vendor/bin/phinx migrate -e production"
    end
  end

  #after :restart, :clear_cache do
    #on roles(:web), in: :groups, limit: 3, wait: 10 do
      # Here we can do anything such as:
      # within release_path do
      #   execute :rake, 'cache:clear'
      # end
    #end
  #end
end


task :post_deploy do
    task :pepe do
        execute "echo pepe"
    end
end

after "deploy:updated", "deploy:composer_install"
after "deploy:composer_install", "deploy:bower_install"
after "deploy:bower_install", "deploy:database_update"