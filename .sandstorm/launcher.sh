mkdir -p /var/microapp
mkdir -p /var/microapp/pages
mkdir -p /var/www
mkdir -p /var/www/assets
mkdir -p /var/www/audios
mkdir -p /var/www/datas
mkdir -p /var/www/images
mkdir -p /var/www/videos

if [ ! -L "/var/microapp/templates" ]; then
     ln -s /opt/app/templates /var/microapp/templates
fi

rsync --recursive --ignore-existing /opt/app/pages/ /var/microapp/pages/
rsync --recursive --ignore-existing /opt/app/datas/ /var/microapp/datas/

cd /var/microapp
exec /opt/app/microapp --data /var/microapp
exit 0
