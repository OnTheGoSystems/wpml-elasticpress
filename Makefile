# Tutorial: http://www.cs.colby.edu/maxwell/courses/tutorials/maketutor/
# Docs: https://www.gnu.org/software/make/

template:=.make/wpml.mk

# Include the file but do not fail with error if it does not exist
include $(template)

.ONESHELL:
.make/wpml.mk:
	git clone ssh://git@git.onthegosystems.com:10022/wpml/wpml-plugin-template.git .makefiletemp
	cp -a .makefiletemp/.make/. .make/
	rm -rf .makefiletemp

