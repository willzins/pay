
    window.Mercadopago.setPublishableKey("APP_USR-e02d11ef-df2c-4b4a-b8b3-c1f7c54c330e");
    
    window.Mercadopago.getIdentificationTypes();
    
    //document.getElementById('cardNumber').addEventListener('keyup', guessPaymentMethod);
    document.getElementById('cardNumber').addEventListener('change', guessPaymentMethod);
    
    function guessPaymentMethod(event) {
        let cardnumber = document.getElementById("cardNumber").value;

        if (cardnumber.length >= 6) {
            let bin = cardnumber.substring(0,6);
            window.Mercadopago.getPaymentMethod({
                "bin": bin
            }, setPaymentMethod);
        }
    };
    
    function setPaymentMethod(status, response) {
       if (status == 200) {
           let paymentMethod = response[0];
           document.getElementById('paymentMethodId').value = paymentMethod.id;
    
           if(paymentMethod.additional_info_needed.includes("issuer_id")){
               getIssuers(paymentMethod.id);
           } else {
               getInstallments(
                   paymentMethod.id,
                   document.getElementById('transactionAmount').value
               );
           }
       }
    }

    function getInstallments(){
        window.Mercadopago.getInstallments({
            "payment_method_id": document.getElementById('payment_method_id').value,
            "amount": parseFloat(document.getElementById('transaction_amount').value)
            
        }, function (status, response) {
            if (status == 200) {
                document.getElementById('installments').options.length = 0;
                response[0].payer_costs.forEach( installment => {
                    let opt = document.createElement('option');
                    opt.text = installment.recommended_message;
                    opt.value = installment.installments;
                    document.getElementById('installments').appendChild(opt);
                });
            } else {
                alert(`installments method info error: ${response}`);
            }
        });
    }    
    
    doSubmit = false;
    document.querySelector('#pay').addEventListener('submit', doPay);

    function doPay(event){
        event.preventDefault();
        if(!doSubmit){
            var $form = document.querySelector('#pay');

            window.Mercadopago.createToken($form, sdkResponseHandler);

            return false;
        }
    };

    function sdkResponseHandler(status, response) {
        if (status != 200 && status != 201) {
            alert("verify filled data");
        }else{
            var form = document.querySelector('#pay');
            var card = document.createElement('input');
            card.setAttribute('name', 'token');
            card.setAttribute('type', 'hidden');
            card.setAttribute('value', response.id);
            form.appendChild(card);
            doSubmit=true;
            form.submit();
        }
    };    
