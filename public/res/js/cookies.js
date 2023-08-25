function setCookie(name, value) {
    document.cookie = name + "=" + value + ";";
}

function getCookie(name) {
    let value = document.cookie.split('; ')
        .find((row) => row.startsWith(name + '='))
        ?.split('=')[1];
    console.log(value);
    return value;
}

function deleteCookie(name) {
    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT;"
}