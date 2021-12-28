clean:
	# Removes the build directory
	rm -rf build

update:
	# Updates the package.json file
	ppm --generate-package="botsrc"

build:
	# Compiles the package
	mkdir build
	ppm --compile="botsrc" --directory="build"

install:
	# Installs the compiled package to the system
	ppm --fix-conflict --no-prompt --install="build/net.intellivoid.synical_bot.ppm" --branch="master"

install_fast:
	# Installs the compiled package to the system
	ppm --fix-conflict --no-prompt --skip-dependencies --install="build/net.intellivoid.synical_bot.ppm" --branch="master"

run:
	# Runs the bot
	ppm --main="net.intellivoid.synical_bot" --version="latest"
