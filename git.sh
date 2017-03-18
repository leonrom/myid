# git init
git add . 
git commit -m "first commit"
#git remote add origin   https://github.com/leonrom/lscook.git
git pull
git push -u origin master

git log --pretty=oneline
git tag -a v1.0.1 -m 'version 1.0.2' b2e6bb49
git push origin v1.0.1