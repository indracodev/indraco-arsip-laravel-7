Set WshShell = CreateObject("WScript.Shell")
Set Fso = CreateObject("Scripting.FileSystemObject")
ScriptDir = Fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = ScriptDir

' Jalankan launcher utama (Caddy + pool PHP) secara silent, tanpa jendela hitam CMD.
' DMS_SILENT: launcher tidak menunggu tombol dan tidak membuka browser sendiri.
' Hentikan server dengan STOP-DMS-INDRACO.bat.
WshShell.Environment("PROCESS")("DMS_SILENT") = "1"
WshShell.Run "cmd /c """ & ScriptDir & "\START-DMS-INDRACO.bat""", 0, False

' Tunggu sampai PHP benar-benar menjawab (bukan hanya Caddy), maks 2 menit, lalu buka browser
Set Http = CreateObject("MSXML2.ServerXMLHTTP")
For i = 1 To 120
    WScript.Sleep 1000
    On Error Resume Next
    Http.open "GET", "http://127.0.0.1:8000/api/health/ready", False
    Http.send
    If Err.Number = 0 Then
        If Http.status = 200 Then Exit For
    End If
    Err.Clear
    On Error GoTo 0
Next

WshShell.Run "http://127.0.0.1:8000"
