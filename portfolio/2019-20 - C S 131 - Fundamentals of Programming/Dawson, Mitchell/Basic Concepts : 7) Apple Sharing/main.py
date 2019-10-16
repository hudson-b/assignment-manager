numberN=input("How many goats?")
numberK=input("How many apples?")


numberN=int(numberN)
numberK=int(numberK)


numberP=(numberK  //  numberN)
print ("Each goat will receive", numberP ,"apples.")

numberS=(numberK % numberN)
print (numberS, "apples will be left in the basket.")