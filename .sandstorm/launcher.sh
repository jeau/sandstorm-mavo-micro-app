mkdir -p /var/microapp
mkdir -p /var/microapp/pages
mkdir -p /var/microapp/data
mkdir -p /var/microapp/audios
mkdir -p /var/microapp/images
mkdir -p /var/microapp/videos
mkdir -p /var/www

if [ ! -L "/var/microapp/templates" ]; then
     ln -s /opt/app/templates /var/microapp/templates
fi

if [ ! -L "/var/microapp/assets" ]; then
     ln -s /opt/app/assets /var/microapp/assets
fi

rsync --recursive --ignore-existing /opt/app/pages/ /var/microapp/pages/
rsync --recursive --ignore-existing /opt/app/data/ /var/microapp/data/
rsync --recursive --ignore-existing /opt/app/audios/ /var/microapp/audio/
rsync --recursive --ignore-existing /opt/app/images/ /var/microapp/images/
rsync --recursive --ignore-existing /opt/app/videos/ /var/microapp/videos/

cd /var/microapp
exec /opt/app/microapp --data /var/microapp
exit 0
