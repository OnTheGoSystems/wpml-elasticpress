#!/usr/bin/env bash

git archive -o makefile.zip --remote=ssh://git@git.onthegosystems.com:10022/wpml-shared/makefile-git-hooks.git master

unzip -o makefile.zip
rm makefile.zip README.md
git init
make githooks
make install
git add .
