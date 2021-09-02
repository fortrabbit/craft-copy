#!/bin/sh

##
# Craft copy entry point
##
#
# Under MacOS, Docker supports ssh-agent forwarding using the 
# magic (and undocumented!) value /run/host-services/ssh-auth.sock
# however... the socket is then owned by root
# 
# For our usecase we need to run all the craft-copy commands as 
# the php user (www-data), so we use socat while root to create a socket
# owned by www-data that forwards to the docker provided socket, which
# forwards to the host (phew!)
# 

USER="www-data"
GRP="www-data"
MODE=755
SSH_USER_SOCK="/home/$USER/.ssh/socket"
SSH_SYSTEM_SOCK=${SSH_AUTH_SOCK}
DEBUG=false

socat UNIX-LISTEN:${SSH_USER_SOCK},fork,user=${USER},group=${GRP},mode=${MODE} \
    UNIX-CONNECT:${SSH_SYSTEM_SOCK} \
    &

export SSH_AUTH_SOCK=${SSH_USER_SOCK}

if [ $DEBUG == true ]; then
	echo "Command: $@"
fi
su-exec www-data "$@"