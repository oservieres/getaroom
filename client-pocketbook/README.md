# A few words

This app is made for Pocketbook Touch Lux 1 devices. Then it is only compatible with Firmware 4.X.

# How to compile

Assuming you followed these [instructions](https://github.com/pmartin/pocketbook-demo/blob/master/demo01/README.md#firmware-4x) in order to install FW4 SDK :

```
${FRSCSDK}/bin/arm-none-linux-gnueabi-g++  ./getaroom.cpp -o getaroom.app -linkview -lxml2 -lpthread
```

# How to run it

Just copy the .app file in the applications directory of your e-reader.
