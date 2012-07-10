var GET_RANDOM_QUOTE_URL = "http://localhost/quotes/php/get_random_quote.php";
var VERIFY_ANSWER_URL = "http://localhost/quotes/php/verify_answer.php";
var GET_CURRENT_STATE_URL = "http://localhost/quotes/php/verify_answer.php";

var TYPE_MOVIE_ID = 1;
var TYPE_FAMOUS_PEOPLE_ID = 2;

/* info and error codes */
var INFO_CODE_SET_ENDED = 0;
var INFO_CODE_NO_MORE_UNIQUE_QUOTES = 1;

var EFFECTS_LENGTH = 200;
var ACTIVE_OPACITY = 1;
var INACTIVE_OPACITY = 0.6;
var WRONG_OPACITY = 0.2;

var quoteTypeToUse = TYPE_MOVIE_ID;

var currentQuoteText;
var quotesAsked = 0;
var quotesInSet = 0;

var nextQuoteButton;

$(document).ready(init);

function init() {
    nextQuoteButton = document.createElement("a");
    nextQuoteButton.opacity = INACTIVE_OPACITY;
    $(nextQuoteButton).text(">");
    nextQuoteButton.onclick = getRandomQuote;
    $(nextQuoteButton).addClass("nextQuoteButton");
    $(nextQuoteButton).hover(
        function() {
            $(this).stop().animate({opacity: ACTIVE_OPACITY}, EFFECTS_LENGTH);
        },
        function() {
            $(this).stop().animate({opacity: INACTIVE_OPACITY}, EFFECTS_LENGTH);
        });

    getRandomQuote();
}

function getRandomQuote() {
    $.ajax({
        url:GET_RANDOM_QUOTE_URL,
        cache:false,
        data:{origin_type_id:encodeURIComponent(quoteTypeToUse)},
        dataType:"json",
        success:randomQuoteSuccessHandler
    });
}

function randomQuoteSuccessHandler(data) {
    if(data.hasOwnProperty('info')){
        handleInfo(data.info);
    }
    else{
        currentQuoteText = data.quote;
        displayQuote(currentQuoteText);

        displayOrigins(data.origins);

        quotesAsked = data.quotesAsked;
        quotesInSet = data.quotesInSet;
        displayStats(quotesAsked, quotesInSet);

        $("#nextQuestionButtonHolder").empty();
    }
}

function displayQuote(quote) {
    $("#quoteHolder").html(currentQuoteText);
}

function displayOrigins(origins) {
    var originsList = document.createElement("ul");
    $(originsList).addClass("originsList");

    $("#originsHolder").empty();
    $("#originsHolder").append(originsList);

    //create buttons for origins answers
    for (var i = 0; i < origins.length; i++) {
        var originText = origins[i];

        var originButton = document.createElement("a");
        $(originButton).text(originText);
        $(originButton).click(originClickHandlerHelper(originText));
        $(originButton).addClass("originButton");
        $(originButton).hover(
            function() {
                $(this).stop().animate({opacity: ACTIVE_OPACITY}, EFFECTS_LENGTH);
            },
            function() {
                $(this).stop().animate({opacity: INACTIVE_OPACITY}, EFFECTS_LENGTH);
            });

        var originListElement = document.createElement("li");
        $(originListElement).addClass("originListElement");
        $(originListElement).append(originButton);

        $(originsList).append(originListElement);
    }
}

function displayStats(quotesAsked, quotesInSet){
    $("#statsHolder").text(quotesAsked+"/"+quotesInSet);
}

function originClickHandlerHelper(origin) {
    return function originButtonCandler() {
        checkAnswer(currentQuoteText, origin);
    }
}

function checkAnswer(quoteText, originAnswer) {
    $.ajax({
        url:VERIFY_ANSWER_URL,
        cache:false,
        data:{quote_text:encodeURIComponent(quoteText), origin:encodeURIComponent(originAnswer)},
        type:"POST",
        dataType:"json",
        success:checkAnswerSuccessHandler
    })
    return false;
}

function checkAnswerSuccessHandler(data) {
    if(data.hasOwnProperty('info')){
        handleInfo(data.info);
    }
    else{
        handleVerifiedAnswer(data.isUserAnswerCorrect, data.allCorrectAnswers);
    }
}

function handleVerifiedAnswer(isCorrect, allCorrectAnswers) {
    if (isCorrect) {
        handleCorrectAnswer();
    }
    else {
        handleIncorrectAnswer(allCorrectAnswers);
    }

    if(quotesAsked == quotesInSet){
        finishSet();
    }
}

function handleCorrectAnswer() {
    getRandomQuote();
}

function handleIncorrectAnswer(allCorrectAnswers) {
    //leave only wrong and right //answers
    $('#originsHolder ul li').children('a').each(function(){
        if($.inArray(this.text, allCorrectAnswers) != -1){
            $(this).fadeTo(EFFECTS_LENGTH, ACTIVE_OPACITY);
        }
        else{
            $(this).fadeTo(EFFECTS_LENGTH, WRONG_OPACITY);
        }
        $(this).unbind('click');
        $(this).unbind('hover');
        $(this).css("cursor", "default");
    });

    $("#nextQuestionButtonHolder").append(nextQuoteButton);
}

function handleInfo(infoId){
    switch(infoId){
        case INFO_CODE_SET_ENDED:
            finishSet();
            break;
        case INFO_CODE_NO_MORE_UNIQUE_QUOTES:
            alert("no more unique quotes");
            break;
    }
}

function finishSet(){

}