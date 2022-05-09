(function ($, Drupal, drupalSettings) {

    'use strict';
  
    Drupal.behaviors.raveForm = {
      attach: function (context) {
        var options = drupalSettings.rocketfuel.transactionData;
  
        $('.payment-redirect-form', context).on('submit', function () {
          let pay = new getPaidSetup(JSON.parse(options));
          pay.init()
  
          return false;
        });
  
        // Trigger form submission when user visits Payment page.
        $('.payment-redirect-form', context).once('getPaid').trigger('submit');
      }
    };
  
  })(jQuery, Drupal, drupalSettings);

class getPaidSetup{
    constructor(options) {
        this.merchantAuth = options.merchant_auth
        this.amount = options.amount
        this.email = options.customer_email
        this.lastname = options.customer_lastname
        this.firstname = options.cutomer_lastname
        this.uuid = options.uuid
        this.orderId = options.orderId
        this.country = options.country
        this.currency = options.currency
        this.endpoint = options.endpoint
        this.redirect_url = options.redirect_url
        this.environment = options.environment
        this.rkfl = new RocketFuel({
            environment: options.environment
        });
    }

    async init() {
        try {
            await this.initRocketFuel();

        } catch (error) {

            console.log('error from promise', error);

        }

        console.log('Done initiating RKFL');

        this.windowListener();

        if (document.getElementById('rocketfuel_retrigger_payment_button')) {
            document.getElementById('rocketfuel_retrigger_payment_button').addEventListener('click', () => {
                this.startPayment(false);
            });
        }

        this.startPayment();

    }

    async initRocketFuel() {
        return new Promise(async (resolve, reject) => {
            if (!RocketFuel) {
                location.reload();
                reject();
            }

            let payload, response, rkflToken;


            if (this.firstname && this.email && this.merchantAuth) {
                console.log('in')
                payload = {
                    firstName: this.firstname,
                    lastName: this.lastname,
                    email: this.email,
                    merchantAuth: this.merchantAuth,
                    kycType: 'null',
                    kycDetails: {
                        'DOB': "01-01-1990"
                    }
                }


                try {
                    if (this.email !== localStorage.getItem('rkfl_email')) { //remove signon details when email is different
                        localStorage.removeItem('rkfl_token');
                        localStorage.removeItem('access');

                    }

                    rkflToken = localStorage.getItem('rkfl_token');

                    if (!rkflToken) {

                        response = await this.rkfl.rkflAutoSignUp(payload, this.environment);

                        this.setLocalStorage('rkfl_email', this.email);

                        if (response) {

                            rkflToken = response.result?.rkflToken;

                        }

                    }

                    const rkflConfig = {
                        uuid: this.uuid,
                        callback: this.updateOrder,
                        environment: this.environment
                    }
                    if (rkflToken) {
                        rkflConfig.token = rkflToken;
                    }

                    console.log({rkflConfig});

                    this.rkfl = new RocketFuel(rkflConfig);

                    resolve(true);

                } catch (error) {

                    reject();

                }

            }

            resolve('no auto');
        })

    }

    setLocalStorage(key,value){
        localStorage.setItem(key,value);
    }

    updateOrder(result) {
        try {
            let result_status = parseInt(result.status);

            let fd = new FormData();
            fd.append("order_id", this.orderId);
            fd.append("status", result_status);
            fetch(this.redirect_url, {
                method: "POST",
                body: fd
            }).then(res => res.json()).then(result => {
                console.log(result)

            }).catch(e => {
                console.log(e)

            })
        } catch (error) {

        }

    }

    startPayment(autoTriggerState = true) {

        /*if (!autoTriggerState) {
            document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Preparing Payment window...";
            this.watchIframeShow = true;
        }*/

        // document.getElementById('rocketfuel_retrigger_payment_button').disabled = true;

        let checkIframe = setInterval(() => {

            if (this.rkfl.iframeInfo.iframe) {
                this.rkfl.initPayment();
                clearInterval(checkIframe);
            }

        }, 500);

    }

    windowListener() {
        window.addEventListener('message', (event) => {

            switch (event.data.type) {
                case 'rocketfuel_iframe_close':
                    //todo trigger the onClose
                    break;
                case 'rocketfuel_new_height':
                    /*if (engine.watchIframeShow) {
                        engine.prepareProgressMessage();
                        engine.watchIframeShow = false;

                    }*/
                    break;
                default:
                    break;
            }

        })
    }
}