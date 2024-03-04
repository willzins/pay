(function(win,doc){
    "use strict";

    //Public Key
    var key = public_key;
    
    window.Mercadopago.setPublishableKey(key);

    //Docs Type
    window.Mercadopago.getIdentificationTypes();

    //Card bin
    function cardBin(event) {
        let textLength=event.target.value.length;
        if(textLength >= 6){
            let bin=event.target.value.substring(0,6);
            window.Mercadopago.getPaymentMethod({
                "bin": bin
            }, setPaymentMethodInfo);

            Mercadopago.getInstallments({
                "bin": bin,
                "amount": parseFloat(document.querySelector('#transaction_amount').value),
            }, setInstallmentInfo);
        }
    }

    if(doc.querySelector('#cardNumber')){
        let cardNumber=doc.querySelector('#cardNumber');
        cardNumber.addEventListener('keyup',cardBin,false);
    }


    //Set Payment
    function setPaymentMethodInfo(status, response) {
        if (status == 200) {
            const paymentMethodElement = doc.querySelector('input[name=payment_method_id]');
            paymentMethodElement.value=response[0].id;
            doc.querySelector('.brand').innerHTML="<img src='"+response[0].thumbnail+"' alt='Bandeira do Cartão'>";
        } else {
            alert(`payment method info error: ${response}`);
        }
    }

    //Set Installments
    function setInstallmentInfo(status, response) {
        let label=response[0].payer_costs;
        let installmentsSel=doc.querySelector('#installments');
        installmentsSel.options.length=0;
    
        label.map(function(elem,ind,obj){
        let txtOpt=elem.recommended_message;
        let valOpt=elem.installments;
        installmentsSel.options[installmentsSel.options.length]=new Option(txtOpt,valOpt);
        });
    
    };

    //Create Token
    function sendPayment(event) {
        event.preventDefault();
        window.Mercadopago.createToken(event.target, sdkResponseHandler);
    }

    function sdkResponseHandler(status, response) {
        if (status == 200 || status == 201) {
            let form = doc.querySelector('#pay');
            let card = doc.createElement('input');
            card.setAttribute('name', 'token');
            card.setAttribute('type', 'hidden');
            card.setAttribute('value', response.id);
            form.appendChild(card);
            form.submit();
        }
    }

    if(doc.querySelector('#pay')){
        let formPay=doc.querySelector('#pay');
        formPay.addEventListener('submit',sendPayment,false);
    }

    
})(window,document);
    