hide = (...elements) =>{
    for (let e of elements){
        document.getElementById(e).classList.add('hidden');}
}
show = (...elements) =>{
    for (let e of elements){
        document.getElementById(e).classList.remove('hidden')}
}