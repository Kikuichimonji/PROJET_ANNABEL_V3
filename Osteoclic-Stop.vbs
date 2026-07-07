' Arrete le serveur PHP demarre par Osteoclic.vbs.

Option Explicit

Dim fso, shell, projectDir, pidFile, pid
Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

projectDir = fso.GetParentFolderName(WScript.ScriptFullName)
pidFile = projectDir & "\var\osteoclic.pid"

If Not fso.FileExists(pidFile) Then
    MsgBox "Osteoclic ne semble pas etre demarre (aucun fichier pid trouve).", vbInformation, "Osteoclic"
    WScript.Quit 0
End If

On Error Resume Next
pid = Trim(fso.OpenTextFile(pidFile, 1).ReadLine())
On Error Goto 0

If pid <> "" And IsNumeric(pid) Then
    shell.Run "taskkill /PID " & pid & " /F", 0, True
End If

fso.DeleteFile pidFile, True

MsgBox "Osteoclic est arrete.", vbInformation, "Osteoclic"
