G=float(input('How many goats?'))
A=float(input('How many apples?'))
print ('Each goat will receive', int(A//G), 'apples')
Level=int(A//G)
Rem=A%G
print (int(Rem), 'apples will be left in the basket.')