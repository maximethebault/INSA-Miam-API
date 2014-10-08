# INSA-Miam-API

Sources of the backend of the Android application INSA-Miam

## Requirements

To make the code work, you need to set up a basic redirect from all non-existing files to index.php

Example for nginx:

```nginx

location / {
          try_files $uri $uri/ /index.php?$args;
}
```