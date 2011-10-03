function ObjById(id) 
{ 
    if (document.getElementById) 
        var returnVar = document.getElementById(id); 
    else if (document.all) 
        var returnVar = document.all[id]; 
    else if (document.layers) 
        var returnVar = document.layers[id]; 
    return returnVar; 
}
