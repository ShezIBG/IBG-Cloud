#!/bin/sh

ANGULAR_MODULES="v3 configurator"
# "34.244.34.123",
TEST_SERVER_HOST="34.244.34.123"
TEST_SERVER_DIRECTORY="/var/www/html/eticom"

PROD_SERVER_HOST="3.250sssss.0.66"
PROD_SERVER_DIRECTORY="/var/www/eticom02"

check_dependencies() {
	RESULT=`command -v ng`
	if [ "$RESULT" = "" ]; then
		print_error "ng not found. Angular CLI must be installed to build."
		echo "Install node.js and use npm: npm -g install "
		exit
	fi
}

show_help() {
	echo "Eticom Cloud application build and deployment tool - v1.0"
	echo ""
	echo "usage:"
	echo "   ./build.sh command [options]"
	echo "   ./build.sh test module"
	echo ""
	echo "Available commands:"
	echo "   help      Show this usage info"
	echo "   clean     Clears out Angular build folders and NPM modules"
	echo "   npm       Runs npm install in Angular modules"
	echo "   angular   Build all angular modules (production build)"
	echo "   build     Runs 'npm' and 'angular'"
	echo "   deploy    Copies distribution files to the server"
	echo "   all       Runs 'npm', 'angular' and 'deploy'"
	echo "   test      Runs a single Angular module in watch mode"
	echo ""
	echo "Options:"
	echo "   --prod    Production mode (use live server for deployment)"
	echo ""
	echo "For this program to work properly, public key authentication must be set up to give automatic ssh access to the following servers:"
	echo "   Test      $TEST_SERVER_HOST"
	echo "   Prod      $PROD_SERVER_HOST"
	echo ""
}

print_header() {
	TC_BG="\033[48;5;234m"
	TC_BOLD="\033[1m"
	TC_MODULE="\033[38;5;87m"
	TC_SECTION="\033[38;5;74m"
	TC_TOEOL="\x1B[K"
	TC_OFF="\033[0m"
	echo "${TC_BG}${TC_BOLD}⚡ Processing: ${TC_MODULE}$1${TC_SECTION} ➡ $2${TC_TOEOL}${TC_OFF}"
}

print_error() {
	TC_BOLD="\033[1m"
	TC_RED="\033[91m"
	TC_TOEOL="\x1B[K"
	TC_OFF="\033[0m"
	echo "${TC_BOLD}${TC_RED}✖ ERROR: $1${TC_OFF}"
}

print_success() {
	TC_BOLD="\033[1m"
	TC_GREEN="\033[92m"
	TC_TOEOL="\x1B[K"
	TC_OFF="\033[0m"
	echo "${TC_BOLD}${TC_GREEN}✔ $1${TC_OFF}"
}

run_clean() {
	for MODULE in $ANGULAR_MODULES; do
		print_header "$MODULE" "clean"
		if [ -d "$MODULE" ]; then
			print_header "$MODULE" "deleting Angular dist folder"
			rm -rf ./$MODULE/dist
			print_header "$MODULE" "deleting node_modules folder"
			rm -rf ./$MODULE/node_modules
		else
			print_error "Angular module not found."
		fi
	done
}

run_npm() {
	for MODULE in $ANGULAR_MODULES; do
		print_header "$MODULE" "npm"
		if [ -d "$MODULE" ]; then
			cd $MODULE
			print_header "$MODULE" "npm install"
			npm install
			cd ..
		else
			print_error "Angular module not found."
		fi
	done
}

run_angular() {
	for MODULE in $ANGULAR_MODULES; do
		print_header "$MODULE" "angular"
		if [ -d "$MODULE" ]; then
			cd $MODULE
#			print_header "$MODULE" "ng build --prod"
#			ng build --prod
			print_header "$MODULE" "node --max_old_space_size=8192 'node_modules/@angular/cli/bin/ng' build --prod"
			node --max_old_space_size=8192 'node_modules/@angular/cli/bin/ng' build --prod
			cd ..
		else
			print_error "Angular module not found."
		fi
	done
}

run_deploy() {
	print_header "$SERVER_HOST" "deploy to $SERVER_DIRECTORY"

	# Create user upload folder if needed and sort out permissions
	print_header "$SERVER_HOST" "ssh $SERVER_HOST mkdir $SERVER_DIRECTORY/user-content"
	print_header "$SERVER_HOST" "ssh $SERVER_HOST chmod 777 $SERVER_DIRECTORY/user-content"

	# configurator
	print_header "$SERVER_HOST" "ssh $SERVER_HOST rm -rf $SERVER_DIRECTORY/configurator/dist"
	ssh $SERVER_HOST rm -rf $SERVER_DIRECTORY/configurator/dist
	print_header "$SERVER_HOST" "scp -r ./configurator/dist $SERVER_HOST:$SERVER_DIRECTORY/configurator"
	scp -r ./configurator/dist $SERVER_HOST:$SERVER_DIRECTORY/configurator
	print_header "$SERVER_HOST" "ssh $SERVER_HOST mv $SERVER_DIRECTORY/configurator/dist/index.html $SERVER_DIRECTORY/configurator/dist/index.php"
	ssh $SERVER_HOST mv $SERVER_DIRECTORY/configurator/dist/index.html $SERVER_DIRECTORY/configurator/dist/index.php

	# v3
	print_header "$SERVER_HOST" "ssh $SERVER_HOST rm -rf $SERVER_DIRECTORY/v3/dist"
	#ssh $SERVER_HOST rm -rf $SERVER_DIRECTORY/v3/dist
	print_header "$SERVER_HOST" "scp -r ./v3/dist $SERVER_HOST:$SERVER_DIRECTORY/v3"
	scp -r ./v3/dist $SERVER_HOST:$SERVER_DIRECTORY/v3
	print_header "$SERVER_HOST" "ssh $SERVER_HOST mv $SERVER_DIRECTORY/v3/dist/index.html $SERVER_DIRECTORY/v3/dist/index.php"
	ssh $SERVER_HOST mv $SERVER_DIRECTORY/v3/dist/index.html $SERVER_DIRECTORY/v3/dist/index.php
}

run_test() {
	FOUND=""
	IP_ADDRESS=`ifconfig | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1' | sort -r | head -n 1`

	for MODULE in $ANGULAR_MODULES; do
		if [ "$MODULE" = "$TEST_MODULE" ]; then
			FOUND="1"
			print_header "$MODULE" "test"

			cd $MODULE
			print_header "$MODULE" "ng serve --host $IP_ADDRESS"
			ng serve --host $IP_ADDRESS
			cd ..
			print_success "finished.\n"
		fi
	done

	if [ "$FOUND" = "" ]; then
		print_error "Module not found."
		echo "Modules available: $ANGULAR_MODULES"
	fi
}

#
# Main entry point
#

COMMAND=$1
if [ "$#" -gt 0 ]; then shift; fi

PROD=""
TEST_MODULE=""
SERVER_HOST="$TEST_SERVER_HOST"
SERVER_DIRECTORY="$TEST_SERVER_DIRECTORY"

# DO NOT ALLOW TO RUN ON THE SERVER
# This script was designed to run on development machines
CURRENT_DIR=`pwd`
if [ "$CURRENT_DIR" = "$TEST_SERVER_DIRECTORY" -o "$CURRENT_DIR" = "$PROD_SERVER_DIRECTORY" ]; then
	print_error "This script must be run on a development machine"
	echo "It was not designed to be executed on test/production servers"
	exit
fi

# Parse options before continuing
while [ "$1" != "" ]; do
	case $1 in

		--prod)
			PROD="1"
			SERVER_HOST="$PROD_SERVER_HOST"
			SERVER_DIRECTORY="$PROD_SERVER_DIRECTORY"
			;;

		*)
			if [ "$COMMAND" = "test" ]; then
				if [ "$TEST_MODULE" = "" ]; then
					TEST_MODULE="$1"
				else
					COMMAND="help"
					break
				fi
			else
				COMMAND="help"
				break
			fi
			;;

	esac
	if [ "$#" -gt 0 ]; then shift; fi
done

case $COMMAND in

	clean)
		run_clean
		print_success "done.\n"
		;;

	npm)
		run_npm
		print_success "done.\n"
		;;

	angular)
		check_dependencies
		run_angular
		print_success "done.\n"
		;;

	build)
		check_dependencies
		run_npm
		run_angular
		print_success "done.\n"
		;;

	deploy)
		check_dependencies
		run_deploy
		print_success "done.\n"
		;;

	all)
		check_dependencies
		run_npm
		run_angular
		run_deploy
		print_success "done.\n"
		;;

	test)
		check_dependencies
		run_test
		;;

	help|*)
		show_help
		;;

esac
