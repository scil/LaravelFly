# Flow

## A Worker Flow in Mode Simple 

* a new worker process
  * create an app 
    * registerBaseServiceProviders(event,log and routing)
  * create a kernel
  * kernel bootstrap
    * LoadEnvironmentVariables LoadConfiguration HandleExceptions
    * **CleanProviders** see:config/laravelfly 'providers_in_request'
    * RegisterFacades and RegisterProviders
    * **backup**
  * ------ waiting for a request ------
  * ------ when a request arrives ---
  * kernel handle request 
    * **registerConfiguredProvidersInRequest**
    * app->boot
      * fire bootingCallbacks
      * app->booted=true
      * fire bootedCallbacks
    * middleware and router
  * response to client
  * kernel->terminate
    * terminateMiddleware
    * fire app->terminatingCallbacks 
  * **restore**
  * app->booted = false
  * ------ waiting for the 2nd request ------
  * .....(just same as the first request)
  * ------ waiting for the 3ed request ------
  * .....
* the worker process killed when server config 'max_request' reached
* a new worker process
* ......(same as the first worker process).
  


## A Worker Flow in Coroutine Mode 

todo
