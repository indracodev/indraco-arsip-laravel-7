Set WshShell = CreateObject("WScript.Shell")
Set Fso = CreateObject("Scripting.FileSystemObject")
ScriptDir = Fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = ScriptDir

' Gunakan PHP portable dari folder evironment jika tersedia
PhpDir = ScriptDir & "\evironment\php-8.2.29-Win32-vs16-x64"
If Fso.FileExists(PhpDir & "\php.exe") Then
    Set ProcEnv = WshShell.Environment("PROCESS")
    ProcEnv("PATH") = PhpDir & ";" & ProcEnv("PATH")
    ProcEnv("PHPRC") = PhpDir
End If

' Jalankan php artisan serve secara silent (tanpa pop up jendela hitam CMD)
WshShell.Run "cmd /c php artisan serve --host=0.0.0.0 --port=8000", 0, False

' Tunggu 2 detik agar server siap menerima request
WScript.Sleep 2000

' Buka web browser default ke sistem DMS Indraco
WshShell.Run "http://127.0.0.1:8000"
