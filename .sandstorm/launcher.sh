mkdir -p /var/microapp
mkdir -p /var/microapp/pages
mkdir -p /var/www
mkdir -p /var/www/images

if [ ! -L "/var/microapp/templates" ]; then
     ln -s /opt/app/templates /var/microapp/templates
fi

rsync --recursive --ignore-existing /opt/app/pages/ /var/microapp/pages/

cd /var/microapp
exec /opt/app/microapp --data /var/microapp
exit 0
