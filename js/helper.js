var lastCodeMirrorInstance = null

String.prototype.replaceAll = function(search, replacement) {
    var target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
};

function initSolutionBox(useMode){
    
     $("[class=assCodeQuestionCodeBox]").each(function(i, block) {  
        var prog = document.getElementById("assCodeQuestionPreBox").innerText;
         
        var editor = CodeMirror.fromTextArea(block, {
            lineNumbers: true, 
            mode:useMode, 
            theme:"solarized dark",
            firstLineNumber: prog.split("\n").length+1
        });   
        var oid = block.id
        var noChange = false;
        editor.on('change',function(cMirror){
            // get value right from instance
            var yourTextarea = document.getElementById(oid) 
            yourTextarea.value = cMirror.getValue(); 
        });   
        editor.setOption("extraKeys", {
            Tab: function(cm) {
                var spaces = Array(cm.getOption("indentUnit") + 1).join(" ");
                cm.replaceSelection(spaces);
            }
        });  
        lastCodeMirrorInstance = editor
        var inp = editor.display.input;
        
        /*inp.textarea.name = block.name
        inp.textarea.id = block.id
        block.name = ''
        block.id = ''  
        block.parentNode.removeChild(block)*/
    });
}

function builtinRead(x) {
    if (Sk.builtinFiles === undefined || Sk.builtinFiles["files"][x] === undefined)
            throw "File not found: '" + x + "'";
    return Sk.builtinFiles["files"][x];
}

function runPythonInTest(questionID){   
    var prog = document.getElementById("assCodeQuestionPreBox").innerText+"\n";
    //prog += document.getElementById(questionID).value+"\n";
    prog += lastCodeMirrorInstance.getDoc().getValue()+"\n";
    prog += document.getElementById("assCodeQuestionPostBox").innerText;    
    
    runPython(prog)
}

/*function runJava(prog){
    JavaPoly.type('com.javapoly.demo.HomepageDemo').then(
        function(HomepageDemo){
            HomepageDemo.compileAndRun(document.getElementById('usercode').value);
        }
    );
}*/

function runPythonInSolution() { 
   var prog = document.getElementById("resultingCode").innerText;  
   runPython(prog, '');
   var node = document.getElementById("resultingCode_SOL");
   if (node) {
    prog = node.innerText;  
    runPython(prog, '_SOL');
   }
}
function runPython(prog, modifier='') { 
   prog = prog.replaceAll("\t", "  ")
   //alert(prog);
   var mypre = document.getElementById("output"+modifier); 
   console.log(mypre)
   if (mypre){
    mypre.innerHTML = ''; 
    Sk.pre = "output"+modifier;
    Sk.configure({output:function(text) {
        mypre.innerHTML = mypre.innerHTML + text; 
    }, read:builtinRead}); 
    //(Sk.TurtleGraphics || (Sk.TurtleGraphics = {})).target = 'mycanvas';
    var myPromise = Sk.misceval.asyncToPromise(function() {
        return Sk.importMainWithBody("<stdin>", false, prog, true);
    });
    myPromise.then(function(mod) {
        console.log('success', modifier, mod, mod.$d, mod.$d.s.v == "hello");
    },
        function(err) {
             mypre.innerHTML = mypre.innerHTML + '<span style="color:red">'+err.toString()+'</span>';
            console.log(err.toString());
    });
   }
} 