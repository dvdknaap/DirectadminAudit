#!/bin/bash
#if [ "$command" = "/CMD_EMAIL_CATCH_ALL" ] && [ "$update" = "Update"]; then
if [ "$command" = "/CMD_EMAIL_CATCH_ALL" ]; then

  if [ -n "$update" ] && [ -n "$address" ]
  then

      if [[ "$address" =~ "^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" ]]
      then
        exit 0;
      else
        echo "Invalid e-mail address: '$address'";# '$value' '$update' '$action' '$domain'";
        exit 1;
      fi
  fi

fi
exit 0;
